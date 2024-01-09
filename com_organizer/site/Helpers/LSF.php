<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use Exception;
use SimpleXMLElement;
use SoapClient;
use THM\Organizer\Adapters\{Application, Input};

/**
 * Class provides methods for communication with the LSF curriculum documentation system.
 */
class LSF
{
    private SoapClient $client;

    /**
     * Creates the SOAP Client.
     * @throws Exception
     */
    public function __construct()
    {
        $uri          = Input::getParams()->get('wsURI');
        $this->client = new SoapClient(null, ['uri' => $uri, 'location' => $uri]);
    }

    /**
     * Method to perform a soap request based on a certain lsf query
     *
     * @param   string  $query  Query structure
     *
     * @return SimpleXMLElement|false  SimpleXMLElement if the query was successful, otherwise false
     */
    private function getDataXML(string $query): SimpleXMLElement|false
    {
        try {
            $result = $this->client->__soapCall('getDataXML', ['xmlParams' => $query]);
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }

        if (!$result) {
            Application::message('ORGANIZER_SOAP_FAIL', Application::ERROR);

            return false;
        }

        if ($result === 'error in soap-request') {
            Application::message('ORGANIZER_SOAP_INVALID', Application::ERROR);

            return false;
        }

        return simplexml_load_string("<?xml version='1.0' encoding='utf-8'?>" . $result);
    }

    /**
     * Method to get the module by mni number
     *
     * @param   int  $moduleID  The module mni number
     *
     * @return SimpleXMLElement|false
     */
    public function getModule(int $moduleID): SimpleXMLElement|false
    {
        $XML = $this->header('ModuleAll');
        $XML .= "<modulid>$moduleID</modulid>";
        $XML .= '</condition></SOAPDataService>';

        return self::getDataXML($XML);
    }

    /**
     * Performs a soap request, in order to get the xml structure of the given
     * configuration
     *
     * @param   array  $keys  the keys required by LSF to uniquely identify a degree program
     *
     * @return SimpleXMLElement|false
     */
    public function getModules(array $keys): SimpleXMLElement|false
    {
        $XML = $this->header('studiengang');
        $XML .= "<stg>{$keys['program']}</stg>";
        $XML .= "<abschl>{$keys['degree']}</abschl>";
        $XML .= "<pversion>{$keys['accredited']}</pversion>";
        $XML .= '</condition></SOAPDataService>';

        return self::getDataXML($XML);
    }

    /**
     * Creates the header used by all XML queries
     *
     * @param   string  $objectType  the LSF object type
     *
     * @return string  the header of the XML query
     */
    private function header(string $objectType): string
    {
        $params = Input::getParams();

        $header = '<?xml version="1.0" encoding="UTF-8"?><SOAPDataService>';
        $header .= "<general><object>$objectType</object></general><user-auth>";
        $header .= '<password>' . $params->get('wsPassword') . '</password>';
        $header .= '<username>' . $params->get('wsUsername') . '</username>';
        $header .= '</user-auth><condition>';

        return $header;
    }
}
