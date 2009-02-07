<?php
/**
 * Handling users.
 *
 * $Horde: framework/Kolab_Server/test/Horde/Kolab/Server/UserHandlingTest.php,v 1.1.2.3 2009/01/06 15:23:17 jan Exp $
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Server.php';

/**
 * Handling users.
 *
 * $Horde: framework/Kolab_Server/test/Horde/Kolab/Server/UserHandlingTest.php,v 1.1.2.3 2009/01/06 15:23:17 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_UserHandlingTest extends Horde_Kolab_Test_Server {

    /**
     * Test listing userss if there are no users.
     *
     * @scenario
     *
     * @return NULL
     */
    public function listingUsersOnEmptyServer()
    {
        $this->given('an empty Kolab server')
            ->when('listing all users')
            ->then('the list is an empty array');
    }

    /**
     * Test listing users after adding some users.
     *
     * @param array $user_list The users to add.
     *
     * @scenario
     * @dataProvider userLists
     *
     * @return NULL
     */
    public function listingUsersAfterAddingUsers($user_list)
    {
        $this->given('an empty Kolab server')
            ->when('adding an object list', $user_list)
            ->and('listing all users')
            ->then('the list has a number of entries equal to', count($user_list));
    }

    /**
     * Test listing users after adding some users.
     *
     * @param array $user_list The users to add.
     *
     * @scenario
     * @dataProvider userLists
     *
     * @return NULL
     */
    public function listingUserCount($user_list)
    {
        $this->given('an empty Kolab server')
            ->when('adding an object list', $user_list)
            ->and('retriving the result count')
            ->then('the count equals to', count($user_list));
    }

    /**
     * Test the list of users for the user id.
     *
     * @param array $user_list The users to add.
     *
     * @scenario
     * @dataProvider userLists
     *
     * @return NULL
     */
    public function listingUsersHasAttributeId($user_list)
    {
        $this->given('an empty Kolab server')
            ->when('adding a user list', $user_list)
            ->then('the user list contains the unique ID for each user');
    }

    /**
     * Test the list of users for the user type.
     *
     * @param array $user_list The users to add.
     *
     * @scenario
     * @dataProvider userLists
     *
     * @return NULL
     */
    public function listingUsersHasAttributeType($user_list)
    {
        $this->given('an empty Kolab server')
            ->when('adding a user list', $user_list)
            ->then('the user list contains the user type for each user');
    }

    /**
     * Test the list of users for the user full name.
     *
     * @param array $user_list The users to add.
     *
     * @scenario
     * @dataProvider userLists
     *
     * @return NULL
     */
    public function listingUsersHasAttributeFullName($user_list)
    {
        $this->given('an empty Kolab server')
            ->when('adding a user list', $user_list)
            ->then('the user list contains the full name for each user');
    }

    /**
     * Test the list of users for the user mail.
     *
     * @param array $user_list The users to add.
     *
     * @scenario
     * @dataProvider userLists
     *
     * @return NULL
     */
    public function listingUsersHasAttributeEmail($user_list)
    {
        $this->given('an empty Kolab server')
            ->when('adding a user list', $user_list)
            ->then('the user list contains the email for each user');
    }

    /**
     * Test the list of users for the user uid.
     *
     * @param array $user_list The users to add.
     *
     * @scenario
     * @dataProvider userLists
     *
     * @return NULL
     */
    public function listingUsersHasAttributeUid($user_list)
    {
        $this->given('an empty Kolab server')
            ->when('adding a user list', $user_list)
            ->then('the list contains the uid for each user');
    }

    /**
     * @scenario
     * @dataProvider userListByLetter
     */
    public function listingUsersCanBeRestrictedByStartLetterOfTheLastName($letter, $count)
    {
        $this->given('an empty Kolab server')
            ->when('adding user list', $this->largeList())
            ->and('retrieving the result count of a list restricted by the start letter of the last name', $letter)
            ->then('the list contains a correct amount of results', $count);
    }

    /**
     * @scenario
     * @dataProvider userListByLetter
     */
    public function countingUsersCanBeRestrictedByStartLetterOfTheLastName($letter, $count)
    {
        $this->given('an empty Kolab server')
            ->when('adding user list', $this->largeList())
            ->and('retrieving the result count of a list restricted by the start letter of the last name', $letter)
            ->then('the count contains a correct number', $count);
    }

    /**
     * @scenario
     * @dataProvider userListByAttribute
     */
    public function countingUsersCanBeRestrictedByContentsInAnAttribute($attribute, $content, $count)
    {
        $this->given('an empty Kolab server')
            ->when('adding user list', $this->largeList())
            ->and('retrieving the result count of a list restricted by content in an attribute', $attribute, $content)
            ->then('the count contains a correct number', $count);
    }

    /**
     * @scenario
     */
    public function creatingUserWithoutGivenName()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user without first name')
            ->then('the result should indicate an error with', 'Adding object failed: Either the last name or the given name is missing!');
    }

    /**
     * @scenario
     */
    public function creatingUserWithoutLastName()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user without last name')
            ->then('the result should indicate an error with', 'Adding object failed: Either the last name or the given name is missing!');
    }

    /**
     * @scenario
     */
    public function creatingUserWithoutPassword()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user without password')
            ->then('the result should indicate an error with', 'Adding object failed: The value for "userPassword" is missing!');
    }

    /**
     * @scenario
     */
    public function creatingUserWithoutPrimaryMail()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user without primary mail')
            ->then('the result should indicate an error with', 'Adding object failed: The value for "mail" is missing!');
    }

    /**
     * @scenario
     */
    public function creatingUserWithoutTypeCreatesStandardUser()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user without user type')
            ->then('a standard user has been created');
    }

    /**
     * @scenario
     */
    public function creatingUserWithoutInvitationPolicySetsManualPolicy()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user without an invitation policy')
            ->then('the added user has a manual policy');
    }

    /**
     * @scenario
     */
    public function creatingUserWithoutHomeServerFails()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user without a home server')
            ->then('the result should indicate an error with', 'The user cannot be added: The home Kolab server (or network) has not been specified!');
    }

    /**
     * @scenario
     */
    public function creatingUserForDistributedKolabWithoutImapServerFails()
    {
        $this->given('an empty Kolab server')
            ->and('distributed Kolab')
            ->when('adding a user without an imap server')
            ->then('the result should indicate an error with', 'The user cannot be added: The home imap server has not been specified!');
    }

    /**
     * @scenario
     */
    public function creatingUserWithImapServerFailsOnNonDistributedKolab()
    {
        $this->given('an empty Kolab server')
            ->and('monolithic Kolab')
            ->when('adding a user with an imap server')
            ->then('the result should indicate an error with', 'The user cannot be added: A home imap server is only supported with a distributed Kolab setup!');
    }

    /**
     * @scenario
     */
    public function creatingUserWithFreeBusyServerFailsOnNonDistributedKolab()
    {
        $this->given('an empty Kolab server')
            ->and('monolithic Kolab')
            ->when('adding a user with a free/busy server')
            ->then('the result should indicate an error with', 'The user cannot be added: A seperate free/busy server is only supported with a distributed Kolab setup!');
    }

    /**
     * @scenario
     */
    public function modifyingUserMailAddressIsNotAllowed()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user with the mail address "test@example.org"')
            ->and('modifying the mail address to "new@example.org"')
            ->then('the result should indicate an error with', 'The user cannot be modified: Changing the mail address from "test@example.org" to "new@example.org" is not allowed!');
    }

    /**
     * @scenario
     */
    public function modifyingUserHomeServerIsNotAllowd()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user with the home server "test.example.org"')
            ->and('modifying the home server to "new.example.org"')
            ->then('the result should indicate an error with', 'The user cannot be modified: Changing the home server from "test.example.org" to "new.example.org" is not allowed!');
    }

    /**
     * @scenario
     */
    public function modifyingUserImapServerIsNotAllowd()
    {
        $this->given('an empty Kolab server')
            ->and('distributed Kolab')
            ->when('adding a user with the imap server "test.example.org"')
            ->and('modifying the imap server to "new.example.org"')
            ->then('the result should indicate an error with', 'The user cannot be modified: Changing the imap server from "test.example.org" to "new.example.org" is not allowed!');
    }

    /**
     * @scenario
     */
    public function conflictBetweenMailAndMailIsNotAllowed()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user "Test Test" with the mail address "test@example.org"')
            ->and('adding a user "Test2 Test2" with the mail address "test@example.org"')
            ->then('the result should indicate an error with', 'The user cannot be added: Mail address "test@example.org" is already the mail address of user "Test Test"!');
    }

    /**
     * @scenario
     */
    public function conflictBetweenMailAndAliasIsNotAllowed()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user "Test Test" with the mail address "test@example.org"')
            ->and('adding a user with the alias address "test@example.org"')
            ->then('the result should indicate an error with', 'The user cannot be added: Alias address "test@example.org" is already the mail address of user "Test Test"!');
    }

    /**
     * @scenario
     */
    public function conflictBetweenAliasAndAliasIsNotAllowed()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user "Test Test" with the alias address "test@example.org"')
            ->and('adding a user with the alias address "test@example.org"')
            ->then('the result should indicate an error with', 'The user cannot be added: Alias address "test@example.org" is already the alias address of user "Test Test"!');
    }

    /**
     * @scenario
     */
    public function conflictBetweenMailAndUidIsNotAllowed()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user "Test Test" with the mail address "test@example.org"')
            ->and('adding a user with the uid "test@example.org"')
            ->then('the result should indicate an error with', 'The user cannot be added: Uid "test@example.org" is already the mail address of user "Test Test"!');
    }

    /**
     * @scenario
     */
    public function conflictBetweenUidAndUidIsNotAllowed()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user "Test Test" with the uid "test"')
            ->and('adding a user with the uid "test"')
            ->then('the result should indicate an error with', 'The user cannot be added: Uid "test" is already the uid of user "Test Test"!');
    }

    /**
     * @scenario
     */
    public function nonExistingDelegateIsNotAllowed()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user with the delegate address "test@example.org"')
            ->then('the result should indicate an error with', 'The user cannot be added: Delegate address "test@example.org" does not exist!');
    }

    /**
     * @scenario
     */
    public function addingUserInUndefinedDomainIsNotAllowed()
    {
        $this->given('an empty Kolab server')
            ->and('the only served mail domain is "example.org"')
            ->when('adding a user with the mail address "test@doesnotexist.org"')
            ->then('the result should indicate an error with', 'The user cannot be added: Domain "doesnotexist.org" is not being handled by this server!');
    }

    /**
     *  kolab/issue444 (a kolab user may delegate to an external user which should not be possible)
     *
     * @scenario
     */
    public function addingUserWithDelegateInUndefinedDomainIsNotAllowed()
    {
        $this->given('an empty Kolab server')
            ->and('the only served mail domain is "example.org"')
            ->when('adding a user with the delegate mail address "test@doesnotexist.org"')
            ->then('the result should indicate an error with', 'The user cannot be added: Domain "doesnotexist.org" is not being handled by this server!');
    }

    /**
     *   kolab/issue1368 (Webinterface allows to create email addresses with slash that cyrus cannot handle)
     *
     * @scenario
     * @dataProvider invalidMails
     */
    public function disallowInvalidMailAddresses($address)
    {
        $this->given('an empty Kolab server')
            ->when('adding a user with an invalid mail address', $address)
            ->then('the result should indicate an error with', "The user cannot be added: Address \"$address\" is not a valid mail address!");
    }

    /**
     * @scenario
     */
    public function addingUserOnUndefinedHomeServer()
    {
        $this->given('an empty Kolab server')
            ->and('the only home server in the network is "example.org"')
            ->when('adding a user with the home server "doesnotexist.org"')
            ->then('the result should indicate an error with', 'The user cannot be added: Host "doesnotexist.org" is not part of the Kolab network!');
    }

    /**
     * @scenario
     */
    public function addingUserOnUndefinedImapServer()
    {
        $this->given('an empty Kolab server')
            ->and('distributed Kolab')
            ->and('the only imap server in the network is "example.org"')
            ->when('adding a user with the imap server "doesnotexist.org"')
            ->then('the result should indicate an error with', 'The user cannot be added: Imap server "doesnotexist.org" is not part of the Kolab network!');
    }

    /**
     * @scenario
     */
    public function userAttributesCanBeExtended()
    {
        $this->given('an empty Kolab server')
            ->and('an extended attribute "test" has been defined')
            ->when('adding a user with the attribute "test" set to "FIND ME"')
            ->then('the result indicates success')
            ->and('the user can be found using the "test" attribute with the value "FIND ME"');
    }

    /**
     * @scenario
     */
    public function extendedObjectAttributeDescriptionsCanBeRetrieved()
    {
        $this->given('an empty Kolab server')
            ->and('an extended attribute "test" has been defined')
            ->when('retrieving the supported attributes by the object type "user"')
            ->then('the result is an array of Horde attribute descriptions')
            ->and('contains the description of "test"');
    }

    /**
     * @scenario
     */
    public function removingUserFailsIfUserDoesNotExist()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user with the ID "cn=Test Test"')
            ->and('deleting the user with the ID "cn=Dummy Dummy"')
            ->then('the result should indicate an error with', 'The user cannot be deleted: User "cn=Dummy Dummy" does not exist!');
    }

    /**
     * @scenario
     */
    public function removingUserByMailSucceeds()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user with the mail address "test@example.org"')
            ->and('deleting the user with mail address "test@example.org"')
            ->then('the result indicates success')
            ->and('listing all users returns an empty list');
    }

    /**
     * @scenario
     */
    public function removingUserByIdSucceeds()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user with the ID "cn=Test Test"')
            ->and('deleting the user with the ID "cn=Test Test"')
            ->then('the result indicates success')
            ->and('listing all users returns an empty list');
    }

    /**
     * @scenario
     */
    public function addedUserCanLogin()
    {
        $this->given('an empty Kolab server')
            ->and('Horde uses the Kolab auth driver')
            ->when('adding a user with the mail address "test@example.org" and password "test"')
            ->and('trying to login to Horde with "test@example.org" and passowrd "test"')
            ->then('the result indicates success')
            ->and('the session shows "test@example.org" as the current user');
    }

    /**
     * @scenario
     */
    public function allowUserWithExtendedObjectClasses()
    {
        $this->given('an empty Kolab server')
            ->and('an extended set of objectclasses')
            ->when('adding a user with the mail address "test@example.org"')
            ->and('fetching user "test@example.org"')
            ->then('has the additional object classes set');
    }

    /**
     * @scenario
     */
    public function allowToCheckUserPasswords()
    {
        $this->given('an empty Kolab server')
            ->and('password check enabled')
            ->when('adding a user with the mail address "test@example.org" and password "tosimple"')
            ->then('the result should indicate an error with', 'The user cannot be added: The chosen password is not complex enough!');
    }

    /**
     * @scenario
     */
    public function allowToSetAttributeDefaults()
    {
        $this->given('an empty Kolab server')
            ->and('an extended attribute "test" with the default value "test" has been defined')
            ->when('adding a user with the mail address "test@example.org" and an empty attribute "test"')
            ->and('fetching user "test@example.org"')
            ->then('the user object has the attribute "test" set to "test"');
    }

    /**
     * kolab/issue2742 (Have a default quota value when creating new users via the web interface)
     *
     * @scenario
     */
    public function allowToSetDomainSpecificAttributeDefaults()
    {
        $this->given('an empty Kolab server')
            ->and('domain "example.org" is served by the Kolab server')
            ->and('domain "example2.org" is served by the Kolab server')
            ->and('an extended attribute "test" with the default value "test" has been defined')
            ->and('an extended attribute "test" with the default value "test2" has been defined for domain example2.org')
            ->when('adding a user with the mail address "test@example.org" and an empty attribute "test"')
            ->and('adding a user with the mail address "test@example2.org" and an empty attribute "test"')
            ->and('fetching user "test@example.org" and "test@example2.org"')
            ->then('the user "test@example.org" has the attribute "test" set to "test"')
            ->and('the user "test@example2.org" has the attribute "test" set to "test2"');
    }

    /**
     *     kolab/issue3035 (Initialise internal Horde parameters when creating a user)
     *
     * @scenario
     * @dataProvider userAdd
     */
    public function addedUserHasPreferencesInitialized()
    {
        $this->given('an empty Kolab server')
            ->and('Horde uses the Kolab auth driver')
            ->when('adding a user', $user)
            ->and('trying to login to Horde with "test@example.org" and passowrd "test"')
            ->then('the preferences are automatically set to the user information', $user);
    }

    /**
     *      kolab/issue1189 (IMAP login fails on some specific uids)
     *
     * @scenario
     */
    public function userUidsShouldNotResembleTheLocalPartOfMailAddresses()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user "cn=Test Test" with the mail address "test@example.org"')
            ->and('adding a user with the uid "test"')
            ->then('the result should indicate an error with', 'The user cannot be added: The uid "test" matches the local part of the mail address "test@example.org" assigned to user "cn=Test Test"!');
    }

    /**
     *  kolab/issue606 (It is not possible to register people with middlename correctly)
     *
     * @scenario
     */
    public function allowToSetTheMiddleName()
    {
        $this->given('an empty Kolab server')
            ->and('an extended attribute "middleName" has been defined')
            ->when('adding a user with the mail address "test@example.org" and the middle name "Middle"')
            ->and('fetching user "test@example.org"')
            ->then('the user object has the attribute "middleName" set to "Middle"');
    }

    /**
     *   kolab/issue1880 (Poor handling of apostrophes in ldap and admin webpages)
     *
     * @scenario
     */
    public function correctlyEscapeApostrophesInNames()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user with the mail address "test@example.org" and the last name "O\'Donnell"')
            ->and('fetching user "test@example.org"')
            ->then('the user name has the attribute "sn" set to "O\'Donnell"');
    }

    /**
     *    kolab/issue1677 (Allow a user to use an external address as sender)
     *
     * @scenario
     */
    public function allowUserToUseExternalAddressAsSender()
    {
        $this->given('an empty Kolab server')
            ->when('adding a user with the mail address "test@example.org" and the external address "other@doesnotexist.org"')
            ->and('fetching user "test@example.org"')
            ->then('the user has the attribute external address "other@doesnotexist.org"');
    }

    /**
     *     kolab/issue3036 (cn = "givenName sn" ?)
     *
     * @scenario
     */
    public function allowCustomFullnameHandling()
    {
        $this->given('an empty Kolab server')
            ->and('an extended attribute "middleName" has been defined')
            ->and('custom full name handling has been set to "lastname, firstname middlename"')
            ->when('adding a user with the mail address "test@example.org", the last name "Test", the first name "Test", and the middle name "Middle"')
            ->and('fetching user "test@example.org"')
            ->then('the user has the attribute full name "Test, Test Middle"');
    }

}
