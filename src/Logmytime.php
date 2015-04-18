<?php

namespace Dwyera;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use GuzzleHttp\Client;
use Dwyera\UserTask;
use Dwyera\Configuration;

class Logmytime extends Command {

    const ASSEMBLA_ENDPOINT = "https://api.assembla.com";
    const SPACES_GET = "/v1/spaces/%s.%s";
    const SPACES_LIST = "/v1/spaces.%s";
    const TICKETS_ENDPOINT = "/v1/spaces/%s/tickets/%s.%s";
    const FORMAT = "json";
    const SPACE_CACHE_KEY = 'spaces';
    const TICKET_CACHE_KEY = 'tickets';

    protected $headers = "";
    protected $cache = array(self::SPACE_CACHE_KEY => array(), self::TICKET_CACHE_KEY => array());
    protected $client;
    protected $output;

    protected function configure() {
        $this
                ->setName('log')
                ->setDescription('Log time')
                // ToDO make optional
                ->addOption(
                        'space', null, InputOption::VALUE_OPTIONAL, 'Space name?'
                )
                ->addOption(
                        'ticket', null, InputOption::VALUE_OPTIONAL, 'Ticket Number?'
                )
                ->addOption(
                        'apiKey', null, InputOption::VALUE_OPTIONAL, 'Set the API key and save'
                )
                ->addOption(
                        'apiSecret', null, InputOption::VALUE_OPTIONAL, 'Set the API secret and save'
                )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        //TODO create library for assembla
        // command line tool to take space name, ticket number, description, & time - then log time. 
        $this->output = $output;

        // Set the config from command line arguments 
        $this->setAuthConfig($input->getOption('apiKey'), $input->getOption('apiSecret'));

        // Read the latest configurations from file
        $conf = Configuration::readConfig();

        $params = array(
            'base_url' => self::ASSEMBLA_ENDPOINT,
            'defaults' => array(
                'headers' => array('X-Api-Key' => $conf->getKey(), 'X-Api-Secret' => $conf->getSecret())
            )
        );
        $this->client = new Client($params);

        $this->getSpaceList();

        $helper = $this->getHelper('question');

        $spaceName = $input->getOption('space');
        if (!$spaceName) {
            $question = new ChoiceQuestion(
                    'Please select the space: ', array_keys($this->cache[self::SPACE_CACHE_KEY])
            );
            $spaceName = $helper->ask($input, $output, $question);
        }

        $ticketNum = $input->getOption('ticket');
        if (!$ticketNum) {
            $question = new Question('Please enter the number of the ticket: ');
            $ticketNum = $helper->ask($input, $output, $question);
        }
        $spaceId = $this->getSpaceId($spaceName);

        //TODO add option to verify before submitting
        $ticketId = $this->getTicketId($spaceName, $ticketNum);

        $userTask = new UserTask();
        $userTask->setSpaceId($spaceId);
        $userTask->setTicketId($ticketId);
        $userTask->setDescription("test");
        // TODO get from console
        $userTask->setHours(0.1);
        $userTask->setBeginAt(new \DateTime('now'));
        $userTask->setEndAt(new \DateTime('now'));

        $parentTask = new ParentTask();
        $parentTask->setUserTask($userTask);

        $this->output->writeln($parentTask->serialize());
        // TODO Fix this
//        //$client->setDefaultOption('verify', false);
    }

    protected function getSpaceList() {

        $path = sprintf(self::SPACES_LIST, self::FORMAT);
        $this->output->writeln('request: ' . $path);
        $response = $this->client->get($path);
        $jsonString = $response->getBody();

        $spaces = json_decode($jsonString);
//        
//        var_export($spaces);
//return;
        foreach ($spaces as $space) {
            $this->output->writeln($space->id);
            $this->cache[self::SPACE_CACHE_KEY][$space->wiki_name] = $space->id;
        }

        //return $json['id'];
        //SPACES_LIST
    }

    protected function getSpaceId($spaceName) {
        if (array_key_exists($spaceName, $this->cache[self::SPACE_CACHE_KEY])) {
            return $this->cache[self::SPACE_CACHE_KEY][$spaceName];
        }
        return;
        $path = sprintf(self::SPACES_GET, $spaceName, self::FORMAT);
        $this->output->writeln('request: ' . $path);
        $response = $this->client->get($path);
        $json = $response->json();
        print_r("Space ID: " . (String) $json['id']);
        return $json['id'];
    }

    protected function getTicketId($spaceName, $ticketNum) {
        if (array_key_exists($ticketNum, $this->cache[self::TICKET_CACHE_KEY])) {
            return $this->cache[self::TICKET_CACHE_KEY][$ticketNum];
        }
        $path = sprintf(self::TICKETS_ENDPOINT, $spaceName, $ticketNum, self::FORMAT);
        $this->output->writeln($path);

        $response = $this->client->get($path);

        $json = $response->json();
        print_r("Ticket ID: " . (String) $json['id']);
        return $json['id'];
    }

    protected function setAuthConfig($apiKey = null, $apiSecret = null) {
        $configuration = new Configuration;
        $changed = false;
        if ($apiKey) {
            $configuration->setKey($apiKey);
            $changed = true;
        }
        if ($apiSecret) {
            $configuration->setSecret($apiSecret);
            $changed = true;
        }
        if ($changed) {
            $configuration->saveConfig();
        }
    }

}
