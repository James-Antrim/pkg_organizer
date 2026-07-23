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
use SoapClient;
use SoapFault;
use SoapHeader;
use SoapVar;
use stdClass;
use THM\Organizer\Adapters\{Application, Input};

/**
 * Class provides methods for communication with the HIO curriculum documentation system.
 */
class HISinOne
{
    private ?SoapClient $client = null;

    /**
     * Creates the SOAP Client.
     */
    public function __construct()
    {
        $parameters = Input::parameters();

        try {
            $this->client = new SoapClient($parameters->get('wsURI'), ['cache_wsdl' => WSDL_CACHE_NONE, 'trace' => true]);
        } catch (Exception $exception) {
            Application::message('HIO_CLIENT_FAILED', Application::ERROR);
            Application::error($exception->getCode(), $exception->getMessage());
        }

        /** @noinspection HttpUrlsUsage */
        $bsNS1 = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
        $auth  = "<wsse:Security SOAP-ENV:mustUnderstand=\"1\" xmlns:wsse=\"$bsNS1\">";
        $auth  .= "<wsse:UsernameToken><wsse:Username>{$parameters->get('wsUsername')}</wsse:Username>";
        $auth  .= "<wsse:Password>{$parameters->get('wsPassword')}</wsse:Password></wsse:UsernameToken>";
        $auth  .= '</wsse:Security>';

        $header = new SoapHeader($bsNS1, 'Security', new SoapVar($auth, XSD_ANYXML), true);
        $this->client->__setSoapHeaders($header);
    }

    /**
     * Creates a standardized soap fault error output.
     * @param SoapFault $fault the soap fault (exception)
     * @return void
     */
    private function soapFault(SoapFault $fault): void
    {
        // Trailing whitespace to avoid automatic prefixing.
        if ($request = $this->client->__getLastRequest()) {
            Application::message('REQUEST HEADERS', Application::ERROR);
            Application::message('<pre>' . print_r($this->client->__getLastRequestHeaders(), true) . '</pre>', Application::ERROR);
            Application::message('REQUEST ', Application::ERROR);
            Application::message('<pre>' . print_r($request, true) . '</pre>', Application::ERROR);
        }
        if ($response = $this->client->__getLastResponseHeaders()) {
            Application::message('RESPONSE HEADERS', Application::ERROR);
            Application::message('<pre>' . print_r($this->client->__getLastResponseHeaders(), true) . '</pre>', Application::ERROR);
            Application::message('RESPONSE ', Application::ERROR);
            Application::message('<pre>' . print_r($response, true) . '</pre>', Application::ERROR);
        }
        Application::message('FAULT STRING', Application::ERROR);
        Application::message('<pre>' . $fault->faultstring . '</pre>', Application::ERROR);
    }

    /**
     * Method to get the module by mni number
     *
     * @param int $HISinOneID The module mni number
     *
     * @return stdClass|false
     */
    public function subject(int $HISinOneID): stdClass|false
    {
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->client->getModule(['moduleId' => $HISinOneID]);
        } catch (SoapFault $fault) {
            $this->soapFault($fault);
        }

        return false;
    }

    /**
     * Requests program information. If called without identifiers, the catalogue of programs is requested.
     *
     * @param int $HISinOneID the id of the course of study entry in HISinOne
     *
     * @return stdClass|false
     */
    public function program(int $HISinOneID = 0): stdClass|false
    {
        try {
            // Falls Parameter als optional markiert, kann Parameter auch leer gelassen werden
            $params = $HISinOneID ? ['withStructure' => true, 'courseOfStudyId' => $HISinOneID] : ['withStructure' => false];
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->client->getCourseOfStudyWithStructure($params);
        } catch (SoapFault $fault) {
            $this->soapFault($fault);
        }

        return false;
    }
}
