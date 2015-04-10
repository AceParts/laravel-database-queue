<?php namespace Davelip\Queue\Jobs;

use Davelip\Queue\Models\Job;
use Illuminate\Container\Container;

class DatabaseJob extends Illuminate\Queue\Jobs\Job
{
	/**
	 * The job model
	 *
	 * @var Job
	 */
	protected $job;

	/**
	 * job name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Container\Container $container
	 * @param \Davelip\Queue\Models\Job $job
	 */
	public function __construct(Container $container, Job $job)
	{
		$this->job = $job;
		$this->container = $container;
	}

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	public function fire()
	{
		// Get the payload from the job
		$payload = $this->parsePayload($this->job->payload);
		$this->name = $payload['job'];

		// Mark job as started
		$this->job->status = Job::STATUS_STARTED;
		$this->job->save();

		// Fire the actual job
		$this->resolveAndFire($payload);

		// If job is not deleted, mark as finished
		if ( ! $this->deleted) {
			$this->job->status = Job::STATUS_FINISHED;
			$this->job->save();
		}
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		parent::delete();
		$this->job->delete();
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		$now = new Carbon();
		$now->addSeconds($delay);
		$this->job->timestamp = $now->timestamp;
		$this->job->status = Job::STATUS_WAITING;
		$this->job->save();
	}

	/**
	 * Parse the payload to an array.
	 *
	 * @param $payload
	 *
	 * @return array|null
	 */
	protected function parsePayload($payload)
	{
		return json_decode($payload, true);
	}

	/**
	 * Get the name of the queued job class.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get the number of attempts this task has been done
	 *
	 * @return int
	 */
	public function attempts() {
		return ($this->job->retries + 1);
	}

	/**
	 * Get the job identifier.
	 *
	 * @return string
	 */
	public function getJobId()
	{
		return $this->job->getKey();
	}
}
