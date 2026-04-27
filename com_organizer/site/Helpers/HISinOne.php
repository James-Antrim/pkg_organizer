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
 * Class provides methods for communication with the HIO curriculum documentation system.
 */
class HISinOne
{
    private SoapClient $client;

    /**
     * Creates the SOAP Client.
     * @throws Exception
     */
    public function __construct()
    {
        $parameters = Input::parameters();
        $options    = [
            //'location' => $parameters->get('wsURI'),
            'login'    => $parameters->get('wsUsername'),
            'password' => $parameters->get('wsPassword'),
            //'uri' => $parameters->get('wsURI'),
        ];

        //$this->client = new SoapClient(null, $options);
        $this->client = new SoapClient($parameters->get('wsURI'), $options);
    }

    /**
     * Method to perform a soap request based on a certain lsf query
     *
     * @param string $query Query structure
     *
     * @return SimpleXMLElement|false  SimpleXMLElement if the query was successful, otherwise false
     */
    private function request(string $function, string $query): SimpleXMLElement|false
    {
        $finish = '</soapenv:Body></soapenv:Envelope>';
        $start  = '<soapenv:Envelope xmlns:soapenv="https://schemas.xmlsoap.org/soap/envelope/" xmlns:org="https://www.his.de/ws/OrganizerService">';
        $start  .= '<soapenv:Header/><soapenv:Body>';

        try {
            $result = $this->client->__soapCall($function, ['xmlParams' => $start . $query . $finish]);
        } catch (Exception $exception) {
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

        // Since I have to debug ITS responses every time just leave this here.
        /*if (Can::administrate()) {
            echo "<pre>" . print_r("<?xml version='1.0' encoding='utf-8'?>" . $result, true) . "</pre>";die;
        }*/
        return simplexml_load_string("<?xml version='1.0' encoding='utf-8'?>" . $result);
    }

    /**
     * Method to get the module by mni number
     *
     * @param int $moduleID The module mni number
     *
     * @return SimpleXMLElement|false
     */
    public function subject(int $moduleID): SimpleXMLElement|false
    {
        $XML = "<org:getModule><org:moduleId>$moduleID</org:moduleId></org:getModule>";

        return self::request('getModule', $XML);
    }

    /**
     * Requests program information. If called without identifiers, the catalogue of programs is requested.
     *
     * @param string $key the aggregated identifiers used as a key by HISinOne, optional
     *
     * @return SimpleXMLElement|false
     */
    public function program(string $key = ''): SimpleXMLElement|false
    {
        $XML = '<org:getCourseOfStudyWithStructure>';

        // If a specific program is requested, then get its structure.
        if ($key) {
            $XML .= "<org:courseOfStudyId>$key</org:courseOfStudyId><org:withStructure>true</org:withStructure>";
        }

        $XML .= '</org:getCourseOfStudyWithStructure>';

        return self::request('getCourseOfStudyWithStructure', $XML);
    }
}
