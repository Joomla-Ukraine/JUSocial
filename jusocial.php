<?php
/**
 * JUSocial
 *
 * @package          Joomla.Site
 * @subpackage       plg_jusocial
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2017-2018 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

class plgSystemJUSocial extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  3.5
	 */
	protected $app;

	/**
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function onAfterRender()
	{
		if(!$this->app->isClient('site'))
		{
			return true;
		}

		$uri = explode('/', $_SERVER[ 'REQUEST_URI' ]);

		if($uri[ 1 ] == 'account')
		{
			return true;
		}

		$template = $this->app->getTemplate();
		$buffer   = $this->app->getBody();

		preg_match_all('!(<(?:code|pre|textarea|script).*?>.*?</(?:code|pre|textarea|script)>)!si', $buffer, $pre);
		$buffer = preg_replace('!<(?:code|pre|textarea|script).*?>.*?</(?:code|pre|textarea|script)>!si', '#pre#', $buffer);

		$regex = '#\[socpost\](.*?)\[/socpost\]#m';
		if(preg_match_all($regex, $buffer, $match))
		{
			$i = 0;
			foreach($match[ 0 ] as $row)
			{
				$social = htmlspecialchars_decode($match[ 1 ][ $i ]);

				$post   = self::_getTmpl($template, [ 'social' => $social ]);
				$buffer = preg_replace($regex, $post, $buffer, 1);

				$i++;
			}
		}

		if(!empty($pre[ 0 ]))
		{
			foreach($pre[ 0 ] as $tag)
			{
				$buffer = preg_replace('!#pre#!', $tag, $buffer, 1);
			}
		}

		$this->checkBuffer($buffer);
		$this->app->setBody($buffer);

		return true;
	}

	/**
	 * @param $template
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	protected static function _getTmpl($template, array $variables = [])
	{
		$search = JPATH_SITE . '/templates/' . $template . '/html/plg_jusocial/jusocial.php';
		$tmpl   = JPATH_SITE . '/plugins/system/jusocial/tmpl/jusocial.php';

		if( is_file($search) )
		{
			return self::_renderTmpl($search, $variables);
		}

		return self::_renderTmpl($tmpl, $variables);
	}

	/**
	 * @param $template
	 * @param $variables
	 *
	 * @return false|string
	 *
	 * @since 1.0
	 */
	protected static function _renderTmpl($template, $variables)
	{
		ob_start();
		foreach( \func_get_args()[ 1 ] as $key => $value )
		{
			${$key} = $value;
		}

		require \func_get_args()[ 0 ];

		return ob_get_clean();
	}

	/**
	 * @param $buffer
	 *
	 *
	 * @since 1.0
	 */
	private function checkBuffer($buffer)
	{
		if($buffer === null)
		{
			switch(preg_last_error())
			{
				case PREG_BACKTRACK_LIMIT_ERROR:
					$message = 'PHP regular expression limit reached (pcre.backtrack_limit)';
					break;
				case PREG_RECURSION_LIMIT_ERROR:
					$message = 'PHP regular expression limit reached (pcre.recursion_limit)';
					break;
				case PREG_BAD_UTF8_ERROR:
					$message = 'Bad UTF8 passed to PCRE function';
					break;
				default:
					$message = 'Unknown PCRE error calling PCRE function';
			}

			throw new RuntimeException($message);
		}
	}
}