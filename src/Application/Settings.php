<?php
namespace Framework\Application;

/**
 * Lewis Lancaster 2016
 *
 * Class Settings
 *
 * @package Framework
 */

use Framework\Exceptions\ApplicationException;

class Settings
{

	/**
	 * The settings for this web application
	 *
	 * @var array
	 */

	private static $settings = array();

	/**
	 * Gets the settings for this application
	 *
	 * @return array
	 */

	public static function getSettings()
	{

		return self::$settings;
	}


	/**
	 * Writes the settings to file
	 */

	public static function writeSettings()
	{

		$json = json_encode( self::getSettings(), JSON_PRETTY_PRINT );

		if( empty( $json ) )
        {

            throw new ApplicationException();
        }

        file_put_contents( self::fileLocation(), $json );
	}

    /**
     * Removes a setting
     *
     * @param $setting_name
     *
     * @param bool $save
     */

	public static function removeSetting( $setting_name, $save=true )
    {

        unset( self::$settings[ $setting_name ] );

        if( $save )
        {

            self::writeSettings();
        }
    }

    /**
     * Adds a setting
     *
     * @param $name
     *
     * @param $value
     *
     * @param bool $save
     */

    public static function addSetting( $name, $value, $save=true )
    {

        self::$settings[ $name ] = $value;

        if( $save )
        {

            self::writeSettings();
        }
    }

    /**
     * Updates a setting
     *
     * @param $setting_name
     *
     * @param $setting_value
     *
     * @param bool $save
     */

    public static function updateSetting( $setting_name, $setting_value, $save=true )
    {

        self::$settings[ $setting_name ] = $setting_value;

        if( $save )
        {

            Settings::writeSettings();
        }
    }

    /**
     * Returns true if we have that setting
     *
     * @param $setting_name
     *
     * @return bool
     */

	public static function hasSetting( $setting_name )
    {

        if( isset( self::$settings[ $setting_name ] ) == false )
        {

            return false;
        }

        return true;
    }

	/**
	 * Checks is the settings are valid.
	 *
	 * @return bool
	 */

	public static function checkSettings()
	{

		try
		{

			self::getSettings();
		}
		catch( ApplicationException $error )
		{

			return false;
    }

		return true;
	}

    /**
     * @return array
     *
     */

	public static function preloadSettings()
	{

		self::loadSettings();

		if( self::$settings == null )
		{

			throw new ApplicationException();
		}

		return self::$settings;
	}

	/**
	 * @param $setting
	 *
	 * @return mixed
	 */

	public static function getSetting( $setting )
	{

		$settings = self::getSettings();

		if( isset( $settings[ $setting ] ) == false )
		{

			throw new ApplicationException('Setting does not exist');
		}

		$setting = $settings[ $setting ];

		if( is_array( $setting ) == false )
        {

            if( self::hasParsableData( $setting ) )
            {

                return self::parseSetting( $setting );
            }
        }

        return $setting;
	}

    /**
     * Checks if we can parse anything in this setting
     *
     * @param $setting
     *
     * @return bool
     */

	public static function hasParsableData( $setting )
    {

        if( preg_match('/\<(.*?)\>/', $setting ) == false )
        {

            return false;
        }

        return true;
    }

    /**
     * Gets the variables from the setting
     *
     * @param $setting
     *
     * @return array
     */

    private static function getRegexMatch( $setting )
    {

        preg_match("/\<(.*?)\>/",  $setting, $array );

        if( empty( $array ) )
        {

            throw new ApplicationException();
        }


        return $array[1];
    }

    /**
     * This function parses the setting and replaces any of the magic brackets with their respective variable values
     *
     * @param $setting
     *
     * @return array|bool
     */

    public static function parseSetting( $setting )
    {

        $match = self::getRegexMatch( $setting );

        $parsed = null;

        if( Settings::getSetting('settings_php_enabled') )
        {

            try
            {

                eval("\$parsed = {$match};");
            }
            catch( \RuntimeException $error )
            {

                throw new ApplicationException( $error->getMessage() );
            }
        }
        else
        {

            $parsed = $match;
        }

        return self::replaceMatches( $setting, array(
            [
                $match,
                $parsed
            ]
        ));
    }

    /**
     * Replace1s the matches with the variables value
     *
     * @param $setting
     *
     * @param $array
     *
     * @return mixed
     */

    private static function replaceMatches( $setting, $array )
    {

        foreach( $array as $value )
        {

            $setting = str_replace( "<" . $value[0] . ">", $value[1], $setting );
        }

        return $setting;
    }

	/**
	 * Loads the settings
	 */

	private static function loadSettings()
	{

		$settings = self::readSettings();

		if( empty( $settings ) )
		{

			throw new ApplicationException();
		}

		self::$settings = $settings;
	}

    /**
     * Returns true if we can find our settings file ( aka it exists )
     *
     * @return bool
     */

	public static function canFindSettings()
    {

        if( file_exists( self::fileLocation() ) == false )
        {

            return false;
        }

        return true;
    }

	/**
	 * Reads the specific settings
	 *
	 * @return mixed
	 */

	private static function readSettings()
	{

		if( file_exists( self::fileLocation() ) == false )
		{

			throw new ApplicationException();
		}

		return json_decode( file_get_contents( self::fileLocation() ), true );
	}

	/**
	 * The file location of the settings
	 *
	 * @return string
	 */

	private static function fileLocation()
	{

		return sprintf('%s' . DIRECTORY_SEPARATOR . 'data/config' . DIRECTORY_SEPARATOR . 'settings.json', $_SERVER['DOCUMENT_ROOT'] );
	}
}