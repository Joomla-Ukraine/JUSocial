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

jimport('joomla.plugin.plugin');

class plgSystemJUSocial extends JPlugin
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
	 * @since version
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

				$tmpl = self::_getTmpl($template);

				ob_start();
				require $tmpl;
				$post = ob_get_contents();
				ob_end_clean();

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

	protected static function _getTmpl($template)
	{
		$search = JPATH_SITE . '/templates/' . $template . '/html/plg_jusocial/jusocial.php';

		if(is_file($search))
		{
			$tmpl = $search;
		}
		else
		{
			$tmpl = JPATH_SITE . '/plugins/system/plg_jusocial/tmpl/jusocial.php';
		}

		return $tmpl;
	}

	/**
	 * Check the buffer.
	 *
	 * @param   string $buffer Buffer to be checked.
	 *
	 * @return  void
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