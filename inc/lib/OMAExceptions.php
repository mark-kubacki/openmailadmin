<?php
class ConfigurationErrorException
	extends Exception
{};

////////////////////////////////////////////////////////////////////////////////
class OpenmailadminException
	extends Exception
{};

class DataException
	extends OpenmailadminException
{};

class DuplicateEntryException
	extends DataException
{};

////////////////////////////////////////////////////////////////////////////////
class AccessDeniedException
	extends RuntimeException
{};

class ObjectNotFoundException
	extends AccessDeniedException
{};

class UserNotFoundException
	extends ObjectNotFoundException
{};

class AuthenticationFailureException
	extends AccessDeniedException
{};

////////////////////////////////////////////////////////////////////////////////
class IMAPException
	extends Exception
{};

class MailboxCreationError
	extends IMAPException
{};
