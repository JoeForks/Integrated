<?php

namespace Laracasts\Integrated\Extensions;

use Laracasts\Integrated\Database\Connection;
use Laracasts\Integrated\Database\Adapter;
use Symfony\Component\DomCrawler\Form;
use Laracasts\Integrated\Emulator;

abstract class Selenium extends \PHPUnit_Framework_TestCase implements Emulator
{
    use IntegrationTrait;

    /**
     * The Selenium client instance.
     *
     * @var RemoteWebDriver
     */
    protected $client;

    /**
     * Get the base url for all requests.
     *
     * @return string
     */
    public function baseUrl()
    {
        if (isset($this->baseUrl)) {
            return $this->baseUrl;
        }

        return 'http://localhost:4444/wd/hub';
    }

    /**
     * Submit a form on the page.
     *
     * @param  string $buttonText
     * @param  array|null $formData
     * @return self
     */
    public function submitForm($buttonText, $formData = null)
    {
        $this->client()->submit(
            $this->fillForm($buttonText, $formData)
        );

        $this->currentPage = $this->client()->getHistory()->current()->getUri();

        return $this;
    }

    /**
     * Call a URI in the application.
     *
     * @param  string $requestType
     * @param  string $uri
     * @param  array  $parameters
     * @return self
     */
    protected function makeRequest($requestType, $uri)
    {
        $this->crawler = $this->client()->get($uri);

        $this->clearInputs()->assertPageLoaded($uri);

        return $this;
    }

    /**
     * Get a Selenium client instance.
     *
     * @return Client
     */
    protected function client()
    {
        if (! $this->client) {
            $this->client = \RemoteWebDriver::create($this->baseUrl(), \DesiredCapabilities::firefox());
        }

        return $this->client;
    }

    /**
     * Get the number of rows that match the given condition.
     *
     * @param  string $table
     * @param  array $data
     * @return integer
     */
    protected function seeRowsWereReturned($table, $data)
    {
        return $this->getDbAdapter()->table($table)->whereExists($data);
    }

    /**
     * Get the adapter to the database.
     *
     * @return Adapter
     */
    protected function getDbAdapter()
    {
        if (! $this->db) {
            $this->db = new Adapter(new Connection($this->getDbConfig()));
        }

        return $this->db;
    }

    /**
     * Fetch the user-provided PDO configuration.
     *
     * @return object
     */
    protected function getDbConfig()
    {
        return json_decode(file_get_contents('integrated.json'), true)['pdo'];
    }

    /**
     * Get the content from the last response.
     *
     * @return string
     */
    protected function content()
    {
        return (string) $this->client->getPageSource();
    }

    /**
     * Get the status code from the last response.
     *
     * @return integer
     */
    protected function statusCode()
    {
        return 200;
    }

    /**
     * Close the browser after test
     *
     * @return void
     */
    public function tearDown()
    {
        $this->client->close();
    }
}
