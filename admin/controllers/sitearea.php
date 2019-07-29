<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * SiteAreas SiteArea Controller
 */
class SiteAreasControllerSiteArea extends JControllerForm
{
    /**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JControllerLegacy
	 * @throws  \Exception
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
        $this->view_list = 'siteareas';
    }
}
