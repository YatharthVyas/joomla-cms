<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_templates
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Templates\Administrator\View\Style;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * View to edit a template style.
 *
 * @since  1.6
 */
class JsonView extends BaseHtmlView
{
	/**
	 * The Object (on success, false on failure)
	 *
	 * @var  \Joomla\CMS\Object\CMSObject
	 */
	protected $item;

	/**
	 * The form object
	 *
	 * @var  \Joomla\CMS\Form\Form
	 */
	protected $form;

	/**
	 * The model state
	 *
	 * @var  \Joomla\CMS\Object\CMSObject
	 */
	protected $state;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   1.6
	 */
	public function display($tpl = null)
	{
		try
		{
			$this->item = $this->get('Item');
		}
		catch (\Exception $e)
		{
			$app = Factory::getApplication();
			$app->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		$paramsList = $this->item->getProperties();

		unset($paramsList['xml']);

		$paramsList = json_encode($paramsList);

		return $paramsList;
	}
}
