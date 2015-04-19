<?php

namespace Dwyera;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Guzzle\Http\Client;
use Dwyera\UserTask;
use Dwyera\Configuration;

/**
 * Big ugly, messy class that needs to be broken out
 */
class Logmytime extends Command {

    const ASSEMBLA_ENDPOINT = "https://api.assembla.com";
    const SPACES_GET = "/v1/spaces/%s.%s";
    const SPACES_LIST = "/v1/spaces.%s";
    const TICKETS_ENDPOINT = "/v1/spaces/%s/tickets/%s.%s";
    const TASKS_POST_ENDPOINT = "/v1/tasks";
    const FORMAT = "json";
    const SPACE_CACHE_KEY = 'spaces';
    const TICKET_CACHE_KEY = 'tickets';

    protected $headers = "";
    protected $cache = array(self::SPACE_CACHE_KEY => array(), self::TICKET_CACHE_KEY => array());
    protected $client;
    protected $output;
    protected $input;
    protected $helper;

    protected function configure() {
        $this
                ->setName('log')
                ->setDescription('Log time')
                ->addOption(
                        'space', 's', InputOption::VALUE_OPTIONAL, 'Space name'
                )
                ->addOption(
                        'ticket', 't', InputOption::VALUE_OPTIONAL, 'Ticket Number'
                )
                ->addOption(
                        'description', 'd', InputOption::VALUE_OPTIONAL, 'Ticket Description'
                )
                ->addOption(
                        'hours', 'hr', InputOption::VALUE_OPTIONAL, 'Hours to log against ticket. E.g. 1.5'
                )
                ->addOption(
                        'apiKey', 'key', InputOption::VALUE_OPTIONAL, 'Set the API key and save'
                )
                ->addOption(
                        'apiSecret', 'sec', InputOption::VALUE_OPTIONAL, 'Set the API secret and save'
                )
                ->addOption(
                        'interactive', 'i', InputOption::VALUE_NONE, 'Interactively prompt for time logging?'
                )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        
        //TODO create library for assembla
        // command line tool to take space name, ticket number, description, & time - then log time. 
        $this->output = $output;
        $this->input = $input;

        // Set the config from command line arguments 
        $this->setAuthConfig($input->getOption('apiKey'), $input->getOption('apiSecret'));

        // Read the latest configurations from file
        $conf = Configuration::readConfig();

        $params = array(
            'request.options' => array(
                'headers' => array('X-Api-Key' => $conf->getKey(), 'X-Api-Secret' => $conf->getSecret())
            )
        );
        $this->client = new Client(self::ASSEMBLA_ENDPOINT, $params);

        $this->getSpaceList();

        $this->helper = $this->getHelper('question');

//        //$client->setDefaultOption('verify', false);

        if ($input->getOption('interactive')) {
            while (true) {
                $this->createTask();
            }
        } else {
            $this->createTask();
        }
    }

    protected function createTask() {
               
        $spaceName = $this->input->getOption('space');
        if (!$spaceName) {
            $question = new ChoiceQuestion(
                    'Please select the space: ', array_keys($this->cache[self::SPACE_CACHE_KEY])
            );
            $spaceName = $this->helper->ask($this->input, $this->output, $question);
        }

        $ticketNum = $this->input->getOption('ticket');
        if (!$ticketNum) {
            $question = new Question('Please enter the number of the ticket: ');
            $ticketNum = $this->helper->ask($this->input, $this->output, $question);
        }
        $spaceId = $this->getSpaceId($spaceName);

        $hours = $this->getTicketHours();
        $description = $this->getTicketDescription();
        //TODO add option to verify before submitting
        $ticketId = $this->getTicketId($spaceName, $ticketNum);

        $userTask = new UserTask();
        $userTask->setSpaceId($spaceId);
        $userTask->setTicketId($ticketId);
        $userTask->setDescription($description);
        // TODO get from console
        $userTask->setHours($hours);
        //TODO allow this to be set to other days
        $userTask->setBeginAt(new \DateTime('now'));
        $userTask->setEndAt(new \DateTime('now'));

        $parentTask = new ParentTask();
        $parentTask->setUserTask($userTask);

        $taskBody = $parentTask->serialize();
        $this->output->writeln($taskBody);
        $this->postTask($taskBody);
    }

    /**
     * Very ugly duplication. Clean up.
     * @return type
     */
    protected function getTicketDescription() {
        $description = $this->input->getOption('description');
        if (!$description) {
            $question = new Question('Please enter the description: ');
            $description = $this->helper->ask($this->input, $this->output, $question);
        }
        return $description;
    }

    protected function getTicketHours() {
        $hours = $this->input->getOption('hours');
        if (!$hours) {
            $question = new Question('Please enter the number of hours: ');
            $hours = $this->helper->ask($this->input, $this->output, $question);
        }
        return $hours;
    }

    protected function getSpaceList() {

        $path = sprintf(self::SPACES_LIST, self::FORMAT);
        $this->output->writeln('request: ' . $path);
        $request = $this->client->get($path);
        $jsonString = $request->send()->getBody();

        $spaces = json_decode($jsonString);

        foreach ($spaces as $space) {
            $this->output->writeln($space->id);
            $this->cache[self::SPACE_CACHE_KEY][$space->wiki_name] = $space->id;
        }
    }

    protected function getSpaceId($spaceName) {
        if (array_key_exists($spaceName, $this->cache[self::SPACE_CACHE_KEY])) {
            return $this->cache[self::SPACE_CACHE_KEY][$spaceName];
        }

        $path = sprintf(self::SPACES_GET, $spaceName, self::FORMAT);
        $this->output->writeln('request: ' . $path);
        $request = $this->client->get($path);
        $json = $request->send()->json();
        $this->cache[self::SPACE_CACHE_KEY][$spaceName] = $json['id'];
        return $json['id'];
    }

    protected function getTicketId($spaceName, $ticketNum) {
        if (array_key_exists($spaceName . '-' . $ticketNum, $this->cache[self::TICKET_CACHE_KEY])) {
            return $this->cache[self::TICKET_CACHE_KEY][$spaceName . '-' . $ticketNum];
        }
        $path = sprintf(self::TICKETS_ENDPOINT, $spaceName, $ticketNum, self::FORMAT);
        $this->output->writeln($path);

        $request = $this->client->get($path);
        $json = $request->send()->json();
        $this->cache[self::TICKET_CACHE_KEY][$spaceName . '-' . $ticketNum] = $json['id'];
        return $json['id'];
    }

    protected function postTask($taskBody) {
        $request = $this->client->post(self::TASKS_POST_ENDPOINT);
        $request->setBody($taskBody, "application/json");
        $response = $request->send();
        //TODO handle errors
        if ($response->getStatusCode() != 200) {
            $formatter = $this->getHelper('formatter');
            $formattedBlock = $formatter->formatBlock(array("Error!", "Unable to post task"), 'error');
            $this->output->writeln($formattedBlock);
        }
        echo $response->getBody(true);
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
