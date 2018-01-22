<?php

namespace OCA\TimeManager\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\TimeManager\Db\Client;
use OCA\TimeManager\Db\ClientMapper;
use OCA\TimeManager\Db\ProjectMapper;
use OCA\TimeManager\Db\TaskMapper;
use OCA\TimeManager\Db\TimeMapper;
use OCA\TimeManager\Db\CommitMapper;
use OCA\TimeManager\Db\storageHelper;
use OCA\TimeManager\Helper\Cleaner;
use OCA\TimeManager\Helper\UUID;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http;

use OCP\IRequest;

class PageController extends Controller {


	/** @var ClientMapper mapper for client entity */
	protected $clientMapper;
	/** @var ProjectMapper mapper for project entity */
	protected $projectMapper;
	/** @var TaskMapper mapper for task entity */
	protected $taskMapper;
	/** @var TimeMapper mapper for time entity */
	protected $timeMapper;
	/** @var ClientMapper mapper for client entity */
	protected $commitMapper;
	/** @var StorageHelper helper for working on the stored data */
	protected $storageHelper;
	/** @var string user ID */
	protected $userId;

	/**
	 * constructor of the controller
	 * @param string $appName the name of the app
	 * @param IRequest $request an instance of the request
	 * @param ClientMapper $clientMapper mapper for client entity
	 * @param ProjectMapper $projectMapper mapper for project entity
	 * @param TaskMapper $taskMapper mapper for task entity
	 * @param TimeMapper $timeMapper mapper for time entity
	 * @param string $userId user id
	 */
	function __construct($appName,
								IRequest $request,
								ClientMapper $clientMapper,
								ProjectMapper $projectMapper,
								TaskMapper $taskMapper,
								TimeMapper $timeMapper,
								CommitMapper $commitMapper,
								$userId
								) {
		parent::__construct($appName, $request);
		$this->clientMapper = $clientMapper;
		$this->projectMapper = $projectMapper;
		$this->taskMapper = $taskMapper;
		$this->timeMapper = $timeMapper;
		$this->commitMapper = $commitMapper;
		$this->userId = $userId;
		$this->storageHelper = new StorageHelper(
			$this->clientMapper,
			$this->projectMapper,
			$this->taskMapper,
			$this->timeMapper,
			$this->commitMapper,
			$userId
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	function index() {
		return new TemplateResponse('timemanager', 'index');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	function clients() {
		$clients = $this->clientMapper->findAllForCurrentUser();
		return new TemplateResponse('timemanager', 'clients', array('clients' => $clients));
	}

	/**
	 * @NoAdminRequired
	 */
	function addClient($name='Unnamed', $note='') {
		$commit = UUID::v4();
		$this->storageHelper->insertCommit($commit);
		$this->storageHelper->addOrUpdateObject(array(
			'name' => $name,
			'note' => $note,
			'commit' => $commit
		), 'clients');
		$urlGenerator = \OC::$server->getURLGenerator();
		return new RedirectResponse($urlGenerator->linkToRoute('timemanager.page.clients'));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	function projects($client=null) {
		$clients = $this->clientMapper->findAllForCurrentUser();
		if($client) {
			$projects = $this->projectMapper->getObjectsByAttributeValue('client_uuid', $client);
			$client_data = $this->clientMapper->getObjectsByAttributeValue('uuid', $client);
		} else {
			$projects = $this->projectMapper->findAllForCurrentUser();
		}
		return new TemplateResponse('timemanager', 'projects', array('projects' => $projects, 'client' => (($client_data && count($client_data) > 0) ? $client_data[0] : null), 'clients' => $clients));
	}

	/**
	 * @NoAdminRequired
	 */
	function addProject($name, $client) {
		$commit = UUID::v4();
		$this->storageHelper->insertCommit($commit);
		$this->storageHelper->addOrUpdateObject(array(
			'name' => $name,
			'client_uuid' => $client,
			'commit' => $commit
		), 'projects');
		$urlGenerator = \OC::$server->getURLGenerator();
		return new RedirectResponse($urlGenerator->linkToRoute('timemanager.page.projects') . '?client=' . $client);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	function tasks($project) {
		$clients = $this->clientMapper->findAllForCurrentUser();
		$projects = $this->projectMapper->findAllForCurrentUser();
		if($project) {
			$tasks = $this->taskMapper->getObjectsByAttributeValue('project_uuid', $project);
			$project_data = $this->projectMapper->getObjectsByAttributeValue('uuid', $project);
			$client_data = $this->clientMapper->getObjectsByAttributeValue('uuid', $project_data[0]->getClientUuid());
		} else {
			$tasks = $this->taskMapper->findAllForCurrentUser();
		}
		return new TemplateResponse('timemanager', 'tasks', array(
			'tasks' => $tasks,
			'project' => (($project_data && count($project_data) > 0) ? $project_data[0] : null),
			'client' => (($client_data && count($client_data) > 0) ? $client_data[0] : null),
			'projects' => $projects,
			'clients' => $clients
		));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	function times($task) {
		$clients = $this->clientMapper->findAllForCurrentUser();
		$projects = $this->projectMapper->findAllForCurrentUser();
		$tasks = $this->taskMapper->findAllForCurrentUser();
		if($task) {
			$times = $this->timeMapper->getObjectsByAttributeValue('task_uuid', $task);
			$task_data = $this->taskMapper->getObjectsByAttributeValue('uuid', $task);
			$project_data = $this->projectMapper->getObjectsByAttributeValue('uuid', $task_data[0]->getProjectUuid());
			$client_data = $this->clientMapper->getObjectsByAttributeValue('uuid', $project_data[0]->getClientUuid());
		} else {
			$times = $this->timeMapper->findAllForCurrentUser();
		}
		return new TemplateResponse('timemanager', 'times', array(
			'times' => $times,
			'task' => (($task_data && count($task_data) > 0) ? $task_data[0] : null),
			'project' => (($project_data && count($project_data) > 0) ? $project_data[0] : null),
			'client' => (($client_data && count($client_data) > 0) ? $client_data[0] : null),
			'tasks' => $tasks,
			'projects' => $projects,
			'clients' => $clients
		));
	}
}