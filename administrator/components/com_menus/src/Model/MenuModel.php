<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Menus\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Menu Item Model for Menus.
 *
 * @since  1.6
 */
class MenuModel extends FormModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $text_prefix = 'COM_MENUS_MENU';

	/**
	 * Model context string.
	 *
	 * @var  string
	 */
	protected $_context = 'com_menus.menu';

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @since   1.6
	 */
	protected function canDelete($record)
	{
		return Factory::getUser()->authorise('core.delete', 'com_menus.menu.' . (int) $record->id);
	}

	/**
	 * Method to test whether the state of a record can be edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @since   1.6
	 */
	protected function canEditState($record)
	{
		return Factory::getUser()->authorise('core.edit.state', 'com_menus.menu.' . (int) $record->id);
	}

	/**
	 * Returns a Table object, always creating it
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table A database object
	 *
	 * @since   1.6
	 */
	public function getTable($type = 'MenuType', $prefix = '\JTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load the User state.
		$id = $app->input->getInt('id');
		$this->setState('menu.id', $id);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_menus');
		$this->setState('params', $params);

		// Load the clientId.
		$clientId = $app->getUserStateFromRequest('com_menus.menus.client_id', 'client_id', 0, 'int');
		$this->setState('client_id', $clientId);
	}

	/**
	 * Method to get a menu item.
	 *
	 * @param   integer  $itemId  The id of the menu item to get.
	 *
	 * @return  mixed  Menu item data object on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function &getItem($itemId = null)
	{
		$itemId = (!empty($itemId)) ? $itemId : (int) $this->getState('menu.id');

		// Get a menu item row instance.
		$table = $this->getTable();

		// Attempt to load the row.
		$return = $table->load($itemId);

		// Check for a table object error.
		if ($return === false && $table->getError())
		{
			$this->setError($table->getError());

			return false;
		}

		$properties = $table->getProperties(1);
		$value      = ArrayHelper::toObject($properties, CMSObject::class);

		return $value;
	}

	/**
	 * Method to get the menu item form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean    A Form object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_menus.menu', 'menu', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		if (!$this->getState('client_id', 0))
		{
			$form->removeField('preset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_menus.edit.menu.data', array());

		if (empty($data))
		{
			$data = $this->getItem();

			if (empty($data->id))
			{
				$data->client_id = $this->state->get('client_id', 0);
			}
		}
		else
		{
			unset($data['preset']);
		}

		$this->preprocessData('com_menus.menu', $data);

		return $data;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   Form    $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 * @since   3.9.23
	 */
	public function validate($form, $data, $group = null)
	{
		if (!Factory::getUser()->authorise('core.admin', 'com_menus'))
		{
			if (isset($data['rules']))
			{
				unset($data['rules']);
			}
		}

		return parent::validate($form, $data, $group);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function save($data)
	{
		$id         = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('menu.id');
		$isNew      = true;

		// Get a row instance.
		$table = $this->getTable();

		// Include the plugins for the save events.
		PluginHelper::importPlugin('content');

		// Load the row if saving an existing item.
		if ($id > 0)
		{
			$isNew = false;
			$table->load($id);
		}

		// Bind the data.
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Trigger the before event.
		$result = Factory::getApplication()->triggerEvent('onContentBeforeSave', array($this->_context, &$table, $isNew, $data));

		// Store the data.
		if (in_array(false, $result, true) || !$table->store())
		{
			$this->setError($table->getError());

			return false;
		}

		// Trigger the after save event.
		Factory::getApplication()->triggerEvent('onContentAfterSave', array($this->_context, &$table, $isNew));

		$this->setState('menu.id', $table->id);

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to delete groups.
	 *
	 * @param   array  $itemIds  An array of item ids.
	 *
	 * @return  boolean  Returns true on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function delete($itemIds)
	{
		// Sanitize the ids.
		$itemIds = ArrayHelper::toInteger((array) $itemIds);

		// Get a group row instance.
		$table = $this->getTable();

		// Include the plugins for the delete events.
		PluginHelper::importPlugin('content');

		// Iterate the items to delete each one.
		foreach ($itemIds as $itemId)
		{
			if ($table->load($itemId))
			{
				// Trigger the before delete event.
				$result = Factory::getApplication()->triggerEvent('onContentBeforeDelete', array($this->_context, $table));

				if (in_array(false, $result, true) || !$table->delete($itemId))
				{
					$this->setError($table->getError());

					return false;
				}

				// Trigger the after delete event.
				Factory::getApplication()->triggerEvent('onContentAfterDelete', array($this->_context, $table));

				// TODO: Delete the menu associations - Menu items and Modules
			}
		}

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Gets a list of all mod_mainmenu modules and collates them by menutype
	 *
	 * @return  array
	 *
	 * @since   1.6
	 */
	public function &getModules()
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select(
				[
					$db->quoteName('a.id'),
					$db->quoteName('a.title'),
					$db->quoteName('a.params'),
					$db->quoteName('a.position'),
					$db->quoteName('ag.title', 'access_title'),
				]
			)
			->from($db->quoteName('#__modules', 'a'))
			->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'))
			->where($db->quoteName('a.module') . ' = ' . $db->quote('mod_menu'));
		$db->setQuery($query);

		$modules = $db->loadObjectList();

		$result = array();

		foreach ($modules as &$module)
		{
			$params = new Registry($module->params);

			$menuType = $params->get('menutype');

			if (!isset($result[$menuType]))
			{
				$result[$menuType] = array();
			}

			$result[$menuType][] = & $module;
		}

		return $result;
	}

	/**
	 * Custom clean the cache
	 *
	 * @param   string   $group     Cache group name.
	 * @param   integer  $clientId  @deprecated  5.0  No Longer used.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function cleanCache($group = null, $clientId = 0)
	{
		parent::cleanCache('com_menus');
		parent::cleanCache('com_modules');
		parent::cleanCache('mod_menu');
	}
}
