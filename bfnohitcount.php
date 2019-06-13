<?php
/**
* @package   Plugin for preventing recording of article hits.
* @version   0.0.1
* @author    https://www.brainforge.co.uk
* @copyright Copyright (C) 2019 Jonathan Brain. All rights reserved.
* @license	 GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/
 
// no direct access
defined('_JEXEC') or die;

jimport( 'joomla.plugin.plugin');
jimport( 'joomla.environment.browser' );

class plgSystemBFnohitcount extends JPlugin
{
  /**
   */
  function onAfterInitialise()
  {
    if (JFactory::getApplication()->isSite())
    {
      $app = JFactory::getApplication();

      $nohitcount = $app->getUserState( "plg.System.BFnohitcount", null);
      if ($nohitcount === null)
      {
        $nohitcount = false;

        if (!$nohitcount)
        {
          $nohitcount = $this->isCrawler();
        }

        if (!$nohitcount)
        {
          $nohitcount = $this->isInUserGroup();
        }

        if (!$nohitcount)
        {
          $nohitcount = $this->hasIpAddress();
        }

        $app->setUserState( "plg.System.BFnohitcount", $nohitcount);
      }

      if ($nohitcount)
      {
        // For example of useage see : com_content/models/article.php
        JFactory::getApplication()->input->set('hitcount', 0);
      }
    }
  }

  /**
   */
  protected function isCrawler()
  {
    if ($this->params->get('nohitcount_crawlers'))
    {
      if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return false;
      }

      // Catch robots defined in Joomla
      $browser = JBrowser::getInstance();
      if ($browser->isrobot()) {
        return true;
      }

      // Nothing personal, but catch any others we might have noticed
      // We can also detect by IP if really necessary
      $crawlers = array(
          '/robot/',
          '/bot.html',
          'a6-indexer',
          'abachobot',
          'accoona',
          'acoirobot',
          'admantx',
          'affectv',
          'ahrefsbot',
          'altavista',
          'aspseek',
          'baiduspider',
          'betabot',
          'bingbot',
          'blexbot',
          'ccbot',
          'cliqzbot',
          'crawler',
          'croccrawler',
          'curl',
          'dotbot',
          'duckduckbot',
          'duckduckgo',
          'dumbot',
          'estyle',
          'ezooms',
          'geonabot',
          'getintent',
          'googlebot',
          'googlebot-mobile',
          'grapeshotcrawler',
          'id-search bot',
          'idbot',
          'linkdexbot',
          'mediapartners-google',
          'megaindex',
          'msnbot',
          'msrbot',
          'mj12bot',
          'nutch',
          'pagebot',
          'proximic',
          'rambler',
          'rogerbot',
          'scrubby',
          'scoutjet',
          'semrushbot',
          'siteexplorer',
          'spbot',
          'vagabondo',
          'wise guys',
          'yandex',
          'yandexbot'
      );
      $additional = trim($this->params->get('additional-crawlers'));
      if (!empty($additional)) {
        $crawlers = array_merge($crawlers, explode(',', strtolower($additional)));
      }

      $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
      foreach($crawlers as $crawler) {
        $crawler = trim($crawler);
        if (!empty($crawler) && strpos($useragent, $crawler) !== false) {
          return true;
        }
      }
    }

    return false;
  }

  /*
   */
  protected function isInUserGroup()
  {
    $usergrouplist = $this->params->get('nohitcount_usergroups');

    if (!empty($usergrouplist))
    {
      $groups = JAccess::getGroupsByUser(JFactory::getUser()->id);
      $match = array_intersect($usergrouplist, $groups);
      return !empty($match);
    }

    return false;
  }

  /*
   */
  protected function hasIpAddress()
  {
    $ipaddresses = trim($this->params->get('nohitcount_ipaddresses'));
    if (empty($ipaddresses))
    {
      return false;
    }

    $clientip = trim(JFactory::getApplication()->input->server->get('REMOTE_ADDR',''));
    if (empty($clientip))
    {
      return false;
    }

    foreach(explode(',', $ipaddresses) as $ipaddress)
    {
      $ipaddress = trim($ipaddress);
      if (!empty($ipaddress))
      {
        $ipaddress = strtolower($ipaddress);
        if (strpos($ipaddress, '@') === 0)
        {
          $dns = dns_get_record(substr($ipaddress, 1) . '.');
          foreach($dns as $record)
          {
            if ($clientip == @$record->ip)
            {
              return true;
            }
          }
        }
        else
        {
          if (strpos($ipaddress, $clientip) === 0)
          {
            return true;
          }
        }
      }
    }

    return false;
  }
}
?>
