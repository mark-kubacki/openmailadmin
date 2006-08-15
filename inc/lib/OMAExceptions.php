<?php
class ConfigurationErrorException
	extends Exception
{};

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

class IMAPException
	extends Exception
{};

class MailboxCreationError
	extends IMAPException
{};
