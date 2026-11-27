<?php

/**
 * @package     Collector
 *
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;

/**
 * Courriels des propositions :
 * - nouvelle proposition (ou proposition corrigée) : aux adresses de l'option « Adresses à prévenir » ;
 * - proposition validée ou refusée : à son auteur (option « Prévenir l'auteur »), sauf s'il l'a traitée lui-même.
 *
 * Les messages sont préparés (proposalMessages, decisionMessages) puis envoyés (send) : un échec d'envoi
 * n'empêche jamais l'action elle-même, il est seulement signalé.
 */
class ProposalNotifier
{
	/**
	 * Nouvelle proposition enregistrée : prévenir les validateurs
	 */
	public static function notifyProposal(int $collection, int $versionId): void
	{
		self::send(self::proposalMessages($collection, $versionId));
	}

	/**
	 * Proposition validée ($validated) ou refusée : prévenir son auteur
	 */
	public static function notifyDecision(int $collection, int $versionId, bool $validated): void
	{
		self::send(self::decisionMessages($collection, $versionId, $validated));
	}

	/**
	 * Messages pour une nouvelle proposition
	 *
	 * @return  array  [['to' => [adresses], 'subject' => …, 'body' => …]]
	 */
	public static function proposalMessages(int $collection, int $versionId): array
	{
		$params    = ComponentHelper::getParams('com_collector');
		$addresses = array_values(array_unique(array_filter(
			preg_split('/[\s,;]+/', (string) $params->get('notify_emails', '')) ?: [],
			fn ($address) => MailHelper::isEmailAddress($address)
		)));
		$info = self::versionInfo($collection, $versionId);

		if (!$addresses || !$info) {
			return [];
		}

		$author = self::user((int) $info->modified_by);
		$review = Uri::root() . 'administrator/index.php?option=com_collector&view=itemversions&collection=' . $collection
			. '&item=' . (int) $info->item;
		$body   = Text::sprintf(
			$info->is_new ? 'COM_COLLECTOR_MAIL_PROPOSAL_BODY_NEW' : 'COM_COLLECTOR_MAIL_PROPOSAL_BODY_CHANGE',
			$author ? $author->name : '?',
			$info->title,
			$info->collection_name
		);

		if (trim((string) $info->modification) !== '') {
			$body .= "\n\n" . Text::sprintf('COM_COLLECTOR_MAIL_COMMENT', trim((string) $info->modification));
		}

		$body .= "\n\n" . Text::sprintf('COM_COLLECTOR_MAIL_PROPOSAL_REVIEW', $review);

		return [[
			'to'      => $addresses,
			'subject' => Text::sprintf('COM_COLLECTOR_MAIL_PROPOSAL_SUBJECT', self::siteName(), $info->title),
			'body'    => $body,
		]];
	}

	/**
	 * Message pour l'auteur d'une proposition validée ou refusée
	 *
	 * @return  array  [['to' => [adresse], 'subject' => …, 'body' => …]]
	 */
	public static function decisionMessages(int $collection, int $versionId, bool $validated): array
	{
		if (!ComponentHelper::getParams('com_collector')->get('notify_author', 1)) {
			return [];
		}

		$info   = self::versionInfo($collection, $versionId);
		$author = $info ? self::user((int) $info->modified_by) : null;
		$me     = Factory::getApplication()->getIdentity();

		// Pas d'auteur joignable, ou proposition traitée par son auteur lui-même
		if (!$author || $author->block || !MailHelper::isEmailAddress((string) $author->email) || ($me && (int) $me->id === (int) $author->id)) {
			return [];
		}

		// Liens du site sans réécriture d'adresse : justes aussi depuis l'administration
		$mine = Uri::root() . 'index.php?option=com_collector&view=myproposals';

		if ($validated) {
			$item = Uri::root() . 'index.php?option=com_collector&view=item&collection=' . $collection . '&id=' . (int) $info->item;
			$body = Text::sprintf('COM_COLLECTOR_MAIL_VALIDATED_BODY', $author->name, $info->title, $info->collection_name, $item, $mine);
		} else {
			$body = Text::sprintf('COM_COLLECTOR_MAIL_REFUSED_BODY', $author->name, $info->title, $info->collection_name,
				trim((string) $info->refuse_note), $mine);
		}

		return [[
			'to'      => [$author->email],
			'subject' => Text::sprintf($validated ? 'COM_COLLECTOR_MAIL_VALIDATED_SUBJECT' : 'COM_COLLECTOR_MAIL_REFUSED_SUBJECT',
				self::siteName(), $info->title),
			'body'    => $body,
		]];
	}

	/**
	 * Envoi ; un échec est signalé sans interrompre l'action
	 */
	public static function send(array $messages): void
	{
		$app = Factory::getApplication();

		if (!$messages || !$app->get('mailonline', 1)) {
			return;
		}

		foreach ($messages as $message) {
			try {
				$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

				foreach ($message['to'] as $address) {
					$mailer->addRecipient($address);
				}

				$mailer->setSubject($message['subject']);
				$mailer->setBody($message['body']);

				if (!$mailer->send()) {
					throw new \RuntimeException('send');
				}
			} catch (\Throwable $e) {
				$app->enqueueMessage(Text::_('COM_COLLECTOR_MAIL_FAILED'), 'warning');
			}
		}
	}

	/**
	 * Version, avec le titre de l'objet, le nom de la collection et « nouvel objet » (aucune autre version validée)
	 */
	private static function versionInfo(int $collection, int $versionId): ?object
	{
		$db      = Factory::getContainer()->get(DatabaseInterface::class);
		$history = $db->quoteName('#__collector_items_history_' . $collection);

		$info = $db->setQuery(
			'SELECT h.id, h.item, h.state, h.modified_by, h.modification, h.refuse_note, i.fulltitle, c.name AS collection_name,'
			. ' NOT EXISTS (SELECT 1 FROM ' . $history . ' AS a WHERE a.item = h.item AND a.state IN (1, 2) AND a.id <> h.id) AS is_new'
			. ' FROM ' . $history . ' AS h'
			. ' JOIN ' . $db->quoteName('#__collector_items') . ' AS i ON i.id = h.item'
			. ' JOIN ' . $db->quoteName('#__collector') . ' AS c ON c.id = ' . $collection
			. ' WHERE h.id = ' . $versionId
		)->loadObject();

		if (!$info) {
			return null;
		}

		$info->title  = trim((string) $info->fulltitle) !== '' ? trim((string) $info->fulltitle) : '#' . (int) $info->item;
		$info->is_new = (bool) $info->is_new;

		return $info;
	}

	private static function user(int $id): ?object
	{
		if (!$id) {
			return null;
		}

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($id);

		return $user && $user->id ? $user : null;
	}

	private static function siteName(): string
	{
		return (string) Factory::getApplication()->get('sitename');
	}
}
