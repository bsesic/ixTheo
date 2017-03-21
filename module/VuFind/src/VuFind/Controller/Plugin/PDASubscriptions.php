<?php

namespace VuFind\Controller\Plugin;
use VuFind\Exception\LoginRequired as LoginRequiredException,
    Zend\Mvc\Controller\Plugin\AbstractPlugin,
    VuFind\Db\Row\User, VuFind\Record\Cache,
    Zend\Mail\Address;

/**
 * Zend action helper to perform favorites-related actions
 */
class PDASubscriptions extends AbstractPlugin
{
    /**
     * Delete a group of pda-subscriptions.
     *
     * @param array $ids    Array of IDs in source|id format.
     * @param mixed $listID ID of list to delete from (null for all
     * lists)
     * @param User  $user   Logged in user
     *
     * @return void
     */
    public function delete($ids, $listID, $user)
    {
        // Sort $ids into useful array:
        $sorted = [];
        foreach ($ids as $current) {
            list($source, $id) = explode('|', $current, 2);
            if (!isset($sorted[$source])) {
                $sorted[$source] = [];
            }
            $sorted[$source][] = $id;
        }

        // Delete PDA entries one source at a time, using a different object depending
        // on whether we are working with a list or the table.
        if (empty($listID)) {
            foreach ($sorted as $source => $ids) {
                $user->removeResourcesById($ids, $source);
           }
        } else {
            $table = $this->getController()->getTable('UserList');
            $list = $table->getExisting($listID);
            foreach ($sorted as $source => $ids) {
                $list->removeResourcesById($user, $ids, $source);
            }
        }
    }

    function getUserData($userId) {
       $userTable = $this->controller->getServiceLocator()->get('Vufind\DbTablePluginManager')->get('User');
       $select = $userTable->getSql()->select()->where(['id' => $userId]);

       $userRow = $userTable->selectWith($select)->current();
       $ixtheoUserTable = $this->controller->getServiceLocator()->get('Vufind\DbTablePluginManager')->get('IxTheoUser');
       $ixtheoSelect = $ixtheoUserTable->getSql()->select()->where(['id' => $userId]);
       $ixtheoUserRow = $ixtheoUserTable->selectWith($ixtheoSelect)->current();
       $userData = [ 'title' => $ixtheoUserRow->title != "Other" ? $ixtheoUserRow->title . " " : "",
                     'firstname' => $userRow->firstname,
                     'lastname' =>  $userRow->lastname,
                     'email' => $userRow->email,
                     'country' => $ixtheoUserRow->country,
                     'user_type' => $ixtheoUserRow->user_type ];
       return $userData;
    }

    function formatUserData($userData) {
       return [ ($userData['title'] != "" ? $userData['title'] . " " : "") . $userData['firstname'] . " " . $userData['lastname'],
                $userData['email'],
                $userData['country']
              ];
    }

    /*
     * Generic Mail send function
     */
    function sendEmail($recipientEmail, $recipientName, $senderEmail, $senderName, $emailSubject, $emailMessage) {
        try {
            $mailer = $this->controller->getServiceLocator()->get('VuFind\Mailer');
            $mailer->send(
                 new Address($recipientEmail, $recipientName),
                 new Address($senderEmail, $senderName),
                 $emailSubject, $emailMessage
             );
        } catch (MailException $e) {
            $this->flashMessenger()->addMessage($e->getMessage(), 'Error sending email');
        }
    }

    /*
     * Send notification to library
     */
    function sendPDANotificationEmail($post, $user, $data, $id) {
        $userDataRaw = $this->getUserData($user->id);
        $userType = $userDataRaw['user_type'];
        $userData = $this->formatUserData($userDataRaw);
        $senderData = $this->getPDASenderData($userType);
        $recipientData = $this->getPDAInstitutionRecipientData($userType);
        $emailSubject = "PDA Bestellung";
        $addressForDispatch = $post['addressfield'];
        $emailMessage = "Benutzer:\r\n" .  implode("\r\n", $userData) . "\r\n\r\n" .
                        "Versandaddresse:\r\n" . $addressForDispatch . "\r\n\r\n" .
                        "Titel:\r\n" . $this->getBookInformation($id) . "\r\n\r\n" .
                        "Benutzer Typ:\r\n" . $userType;
        $this->sendEmail($recipientData['email'], $recipientData['name'], $senderData['email'], $senderData['name'], $emailSubject, $emailMessage);
    }

    function getBookInformation($id) {
        $recordLoader = $this->controller->getServiceLocator()->get('VuFind\RecordLoader');
        $driver = $recordLoader->load($id, 'Solr', false);
        $year = $driver->getPublicationDates()[0];
        $isbn = $driver->getISBNs()[0];
        return $driver->getAuthorsAsString() . ": " .
               $driver->getTitle() . " " .
               ($year != "" ? "(" . $year. ")" : "") . " " .
               ($isbn != "" ? "ISBN: " . $isbn : "");
    }

    /*
     * Get sender Mail addresses from site configuration
     * @param $realm category e.g. ixtheo, relbib
     */
    function getPDASenderData($realm) {
        $config = $this->controller->getServiceLocator()->get('VuFind\Config')->get('config');
        $site = isset($config->Site) ? $config->Site : null;
        $pda_sender = 'pda_sender_' . $realm;
        $pda_sender_name = 'pda_sender_name';
        $senderEmail = isset($site->$pda_sender) ? $site->$pda_sender : null;
        $senderName = isset($site->$pda_sender_name) ? $site->$pda_sender_name : null;
        return ['email' => $senderEmail, 'name' => $senderName ];
    }

    function getPDAInstitutionRecipientData($realm) {
        $config = $this->controller->getServiceLocator()->get('VuFind\Config')->get('config');
        $site = isset($config->Site) ? $config->Site : null;
        $pda_email = 'pda_email_' . $realm;
        $email = isset($site->$pda_email) ? $site->$pda_email : null;
        $name = "UB Tübingen PDA";
        return ['email' => $email, 'name' => $name];
    }

    function sendPDAUserNotificationEmail($post, $user, $data, $id) {
        $userDataRaw = $this->getUserData($user->id);
        $userType = $userDataRaw['user_type'];
        $userData = $this->formatUserData($userDataRaw);
        $senderData = $this->getPDASenderData($userType);
        $recipientEmail = $userData[1];
        $recipientName = $userData[0];
        $emailSubject = $this->controller->translate("Your PDA Order");
        $postalAddress = $this->controller->translate("You provided the following address") . ":\r\n" . $post['addressfield'] . "\r\n\r\n";
        $bookInformation = $this->controller->translate("Book Information") . ":\r\n" . $this->getBookInformation($id) . "\r\n\r\n";
        $opening = $this->controller->translate("Dear") . " " . $userData[0] . ",\r\n\r\n" .
                   $this->controller->translate("you triggered a PDA order") . ".\r\n";
        $renderer = $this->controller->getServiceLocator()->get('viewmanager')->getRenderer();
        $infoText = $renderer->render($this->controller->forward()->dispatch('StaticPage', array(
            'action' => 'staticPage',
            'page' => 'PDASubscriptionMailInfoText'
        )));
        $emailMessage = $opening . $bookInformation . $postalAddress . $infoText . "\r\n\r\n" . $this->getPDAClosing($userType);
        $this->sendEmail($recipientEmail, $recipientName, $senderData['email'], $senderData['name'], $emailSubject, $emailMessage);
    }

    /*
     * Send unsubscribe notification to library
     */
    function sendPDAUnsubscribeEmail($user, $id) {
        $userDataRaw = $this->getUserData($user->id);
        $userType = $userDataRaw['user_type'];
        $userData = $this->formatUserData($userDataRaw);
        $senderData = $this->getPDASenderData($userType);
        $emailSubject = "PDA Abbestellung";
        $recipientData = $this->getPDAInstitutionRecipientData($userType);
        $emailMessage = "Abbestellung: " . $this->getBookInformation($id) . "\r\n\r\n" .
                         "für: " . $userData[0] . "(" . $userData[1] . ")" . " [Benutzertyp: " . $userType . "]";
        $this->sendEmail($recipientData['email'], $recipientData['name'], $senderData['email'], $senderData['name'], $emailSubject, $emailMessage);
    }

    /*
     * Send unsubscribe notification to user
     */
    function sendPDAUserUnsubscribeEmail($user, $id) {
        $userDataRaw = $this->getUserData($user->id);
        $userType = $userDataRaw['user_type'];
        $userData = $this->formatUserData($userDataRaw);
        $senderData = $this->getPDASenderData($userType);
        $emailSubject = $this->controller->translate("Cancellation of your PDA Order");
        $recipientName = $userData[0];
        $recipientEmail = $userData[1];
        $opening = $this->controller->translate("Dear") . " " . $userData[0] . ",\r\n\r\n" . 
                   $this->controller->translate("you cancelled a PDA order") . ":\r\n";
        $emailMessage = $opening .  $this->getBookInformation($id) . "\r\n\r\n" . $this->getPDAClosing($userType);
        $this->sendEmail($recipientEmail, $recipientName, $senderData['email'], $senderData['name'], $emailSubject, $emailMessage);
    }

    function getPDAClosing($realm) {
        $salutation = ($realm === 'relbib') ? $this->controller->translate("Your Relbib Team") : 
                      $this->controller->translate("Your IxTheo Team");
        return $this->controller->translate("Kind Regards") . "\r\n\r\n" . $salutation;
    }
}
