<?php
/**
 * 
 * Created by PhpStorm.
 * User: mavperi
 * Date: 10/01/2018
 * Time: 15:11
 */


namespace awsReporterSqs;

use Aws\Sdk;

class awsReporterSqs
{
    protected $client;
    protected $queues;
    public $data;
    public $csv;

    public function __construct()
    {
        //initialise
        $this->data = [];
        $this->csv = "";

        //get data
        $this->getClient();
        $this->getAllQueues();
        $this->getSortedResults(); // you must update this if you are not using laravel framework or comment out
        $this->getCSV();
    }

    public function getClient()
    {
        $sdk = new \Aws\Sdk([
                'version' => 'latest',
                'region' => 'aws region',
                'credentials' => [
                    'key' => 'your key',
                    'secret' => 'your key secret',
                ]
            ]
        );
        // Get the client from the builder by namespace
        $this->client = $sdk->createSqs();
    }

    /**
     * get a list of all queues
     */
    private function getAllQueues()
    {
        $result = $this->client->listQueues();
        foreach ($result->get('QueueUrls') as $queueUrl) {
            $this->getQueueVisible($queueUrl);
        }
    }

    /**
     *
     * get values of all visible messages
     *
     * @param $queueURL
     */
    private function getQueueVisible($queueURL)
    {
        $result = $this->client->getQueueAttributes(
            [
                'QueueUrl' => $queueURL, // QueueUrl is required
                'AttributeNames' => ['ApproximateNumberOfMessages']
            ]
        );
        $this->data[$queueURL]['visibleMessages'] = $result->get('Attributes')['ApproximateNumberOfMessages'];
        $this->data[$queueURL]['queue'] = substr($queueURL, strrpos($queueURL, '/') + 1);
    }

    /**
     * sort the results by pending messages
     */
    private function getSortedResults()
    {
        //collect is a laravel framework helper which I use for sorting by volume, if not using laravel you can change this to your prefered option or comment out
        $collection = collect($this->data);
        $this->data = $collection->sortByDesc('visibleMessages');
    }

    /**
     * gets the csv string to save the file
     */
    private function getCSV()
    {
        $lines[] = "queue,messages visbile";
        foreach ($this->data as $key => $queue) {
            if($queue['visibleMessages'] > 0){
                $lines[] = $queue['queue'] . "," . $queue['visibleMessages'];
            }
        }
        $this->csv = implode("\n", $lines);
        //you can now store the csv file somewhere ...
    }
}