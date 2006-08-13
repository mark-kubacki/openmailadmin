<?php
class ConfigurationErrorException
	extends Exception
{};

class AccessDeniedException
	extends RuntimeException
{};

class UserNotFoundException
	extends AccessDeniedException
{};

class AuthenticationFailureException
	extends AccessDeniedException
{};
