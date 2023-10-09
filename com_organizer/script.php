<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\Filesystem;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Class for the execution of processes during changes to the component itself.
 */
class Com_OrganizerInstallerScript
{
    /**
     * Creates the directory for images used by the component
     * @return bool true if the directory exists, otherwise false
     */
    private function createImageDirectory()
    {
        return Filesystem\Folder::create(JPATH_ROOT . '/images/organizer');
    }

    /**
     * Method to install the component. For some unknown reason Joomla will not resolve text constants in this function.
     * All text constants have been replaced by hard coded English texts. :(
     * It also seems that under 3.x this function is ignored if the method is upgrade even if no prior installation
     * existed.
     *
     * @param \stdClass $parent - Parent object calling this method.
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install($parent)
    {
        $dirCreated = $this->createImageDirectory();

        if ($dirCreated) {
            $dirColor   = 'green';
            $dirStatus  = 'The directory /images/organizer has been created.';
            $instColor  = 'green';
            $instStatus = 'THM Organizer was successfully installed.';
        } else {
            $dirColor   = 'red';
            $dirStatus  = 'The directory /images/organizer could not be created.';
            $instColor  = 'yellow';
            $instStatus = 'Problems occurred while installing THM Organizer.';
        }
        ?>
        <fieldset id="com_organizer_fieldset" style="border-radius:10px;">
            <legend>
                <img style="float:none;" src="../components/com_organizer/images/organizer.png"
                     alt="THM Organizer Logo"/>
            </legend>
            <div style="padding-left:17px;">
                <div style="color:#146295; font-size: 1.182em; font-weight:bold; padding-bottom: 17px">
                    Organizer is a component designed to handle the scheduling and planning needs of the
                    University of Applied Sciences Central Hesse in Giessen, Germany.
                </div>
                <div style="width: 100%;">
                    Released under the terms and conditions of the
                    <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU General Public License</a>.
                </div>
                <table style="border-radius: 5px; border-style: dashed; margin-top: 17px;">
                    <tbody>
                    <tr>
                        <td>Directory Status</td>
                        <td><span style='color:
                            <?php echo $dirColor; ?>
                                '>
            <?php echo $dirStatus; ?>
            </span></td>
                    </tr>
                    <tr>
                        <td>Installation Status</td>
                        <td><span style='color:
                            <?php echo $instColor; ?>
                                '>
            <?php echo $instStatus; ?>
            </span></td>
                    </tr>
                    </tbody>
                </table>
                <?php
                if ($dirCreated) {
                    ?>
                    <h4>Please ensure that the Organizer component has write access to the directory mentioned
                        above.</h4>
                    <?php
                } else {
                    ?>
                    <h4>Please ensure that the /images/organizer directory exists.</h4>
                    <?php
                }
                ?>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Removes folder contents before update to ensure removal of deprecated files
     *
     * @param string $type   the type of action being performed with the component.
     * @param object $parent the 'parent' running this script
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function preflight($type, $parent)
    {
        /*$languageFiles = [
            '/language/de-DE/de-DE.com_organizer.ini',
            '/language/de-DE/de-DE.com_organizer.sys.ini',
            '/language/en-GB/en-GB.com_organizer.ini',
            '/language/de-DE/en-GB.com_organizer.sys.ini'
        ];
        foreach ($languageFiles as $languageFile)
        {
            if (file_exists(JPATH_ADMINISTRATOR . $languageFile))
            {
                unlink(JPATH_ADMINISTRATOR . $languageFile);
            }
            if (file_exists(JPATH_SITE . $languageFile))
            {
                unlink(JPATH_SITE . $languageFile);
            }
        }

        $adminFiles = Filesystem\Folder::files(JPATH_ADMINISTRATOR . '/components/com_organizer');

        foreach ($adminFiles as $adminFile)
        {
            Filesystem\File::delete(JPATH_ADMINISTRATOR . '/components/com_organizer/' . $adminFile);
        }

        $adminFolders = Filesystem\Folder::folders(JPATH_ADMINISTRATOR . '/components/com_organizer');

        foreach ($adminFolders as $adminFolder)
        {
            Filesystem\Folder::delete(JPATH_ADMINISTRATOR . '/components/com_organizer/' . $adminFolder);
        }

        $siteFiles = Filesystem\Folder::files(JPATH_ROOT . '/components/com_organizer');

        foreach ($siteFiles as $siteFile)
        {
            Filesystem\File::delete(JPATH_ROOT . '/components/com_organizer/' . $siteFile);
        }

        $siteFolders = Filesystem\Folder::folders(JPATH_ROOT . '/components/com_organizer');

        foreach ($siteFolders as $siteFolder)
        {
            Filesystem\Folder::delete(JPATH_ROOT . '/components/com_organizer/' . $siteFolder);
        }*/
    }

    /**
     * Method to uninstall the component
     *
     * @param object $parent the class calling this method
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function uninstall($parent)
    {
        if (!Filesystem\Folder::delete(JPATH_ROOT . '/images/organizer')) {
            echo Text::_('The directory located at &quot;/images/organizer&quot; could not be removed.');
        }
    }

    /**
     * Provides an output once Joomla! has finished the update process.
     *
     * @param Object $parent \Joomla\CMS\Installer\Adapter\ComponentAdapter
     *
     * @return void
     */
    public function update($parent)
    {
        $logoURL     = 'components/com_organizer/images/organizer.png';
        $licenseLink = '<a href="http://www.gnu.org/licenses/gpl-2.0.html" ';
        $licenseLink .= 'target="_blank">GNU General Public License</a>';
        $version     = (string) $parent->get('manifest')->version;

        $dirSpan   = '';
        $imagePath = '/images/organizer';
        if (!$this->createImageDirectory()) {
            $failText = sprintf(Text::_('ORGANIZER_IMAGE_FOLDER_FAIL'), $imagePath);
            $dirSpan  .= '<span style="color:red" >' . $failText . '</span>';
        }
        $updateText = sprintf(Text::_('ORGANIZER_UPDATE_MESSAGE'), $version, $licenseLink);
        ?>
        <div class="span5 form-vertical">
            <?php echo HTMLHelper::_('image', $logoURL, Text::_('ORGANIZER')); ?>
            <br/>
            <p><?php echo $updateText . ' ' . $dirSpan; ?></p>
            <br/>
        </div>
        <?php
    }
}
