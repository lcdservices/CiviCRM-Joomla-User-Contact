<?php

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgUserJoomlaUserContact extends JPlugin
{
    function plgUserJoomlaUserContact(& $subject, $config)
    {
        parent::__construct($subject, $config);
    }

    function onAfterStoreUser($user, $isnew, $success, $msg)
    {

        // Instantiate CiviCRM
        require_once JPATH_ROOT.'/'.'administrator/components/com_civicrm/civicrm.settings.php';
        require_once 'CRM/Core/Config.php';

        if ($isnew && $success) {

            require_once 'CRM/Core/DAO/UFMatch.php';
            $config = CRM_Core_Config::singleton( );
            $uf = $config->userFramework;

            $ufmatch = new CRM_Core_DAO_UFMatch( );
            $ufmatch->uf_name = $user['email'];

            // check if there is already existing match for user with email
            if ( ! $ufmatch->find( true ) ) {
                require_once 'CRM/Contact/BAO/Contact.php';
                $ufmatch->uf_name        = $user['email'];
                $ufmatch->uf_id          = $user['id'];
                if ( CRM_Core_DAO::checkFieldExists('civicrm_uf_match', 'domain_id') ) {
                    $ufmatch->domain_id = CRM_Core_Config::domainID( );
                }
                $cType= 'Individual';

                // check if contact with that email already exists
                $dao =& CRM_Contact_BAO_Contact::matchContactOnEmail( $user['email'], $cType );
                if ($dao) {
                    $ufmatch->contact_id     = $dao->contact_id;
                } else {
                    // data mapping for Joomla User - CiviCRM Contact
                    $params = array ('email-Primary' => $user['email'],
                                     'contact_type'  => $cType,
                                     'display_name'  => $user['username'],
                                    );
                    if ( ! empty( $user['name'] ) ) {
                        require_once 'CRM/Utils/String.php';
                        CRM_Utils_String::extractName( $user['name'], $params );
                    }
                    $contactId = CRM_Contact_BAO_Contact::createProfileContact( $params, CRM_Core_DAO::$_nullArray );
                    $ufmatch->contact_id     = $contactId;
                }
                $ufmatch->save( );
                $ufmatch->free();
            }
        }
    }

}
