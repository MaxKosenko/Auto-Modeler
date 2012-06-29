<?php

include_once 'classes/automodeler/model.php';
include_once 'classes/automodeler/exception.php';
include_once 'classes/automodeler/repository/database.php';
include_once 'classes/automodeler/exception/validation.php';

class DescribeAutoModelerRepositoryDatabase extends \PHPSpec\Context
{
	protected $default_validation;

	public function before()
	{
		$validation = Mockery::mock('Validation');
		$validation->shouldReceive('bind');
		$validation->shouldReceive('rules');
		$validation->shouldReceive('check')->andReturn(TRUE);
		$this->default_validation = $validation;
	}

	public function itShouldCreateALoadedModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Insert');
		$qb->shouldReceive('values')
			->with(Mockery::type('array'))
			->once();
		$qb->shouldReceive('execute')
			->once();

		$model = new AutoModeler_Model(array('id', 'foo'));

		$repository = AutoModeler_Repository_Database::factory($database, 'foos');

		$this->default_validation->shouldReceive('errors')->andReturn(array());
		$new_model = $repository->create($model, NULL, $qb, $this->default_validation);
		$this->spec($new_model->state())->should->be(AutoModeler_Model::STATE_LOADED);
	}

	public function itShouldCreateAModelWithAnId()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Insert');
		$qb->shouldReceive('values')
			->with(Mockery::type('array'))
			->once();
		$qb->shouldReceive('execute')
			->once()
			->andReturn(array(1));

		$model = new AutoModeler_Model(array('id', 'foo'));

		$repository = AutoModeler_Repository_Database::factory($database, 'foos');

		$new_model = $repository->create($model, NULL, $qb, $this->default_validation);
		$this->spec($new_model->id)->should->be(1);
	}

	public function itShouldThrowExceptionWhenCreatingLoadedModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Insert');

		$model = new AutoModeler_Model;
		$model->state(AutoModeler_Model::STATE_LOADED);
		$repository = AutoModeler_Repository_Database::factory($database, 'foos');

		$this->spec(
			function() use ($model, $repository, $qb)
			{
				$validation = Mockery::mock('Validation');
				$repository->create($model, NULL, $qb, $validation);
			}
		)->should->throwException('AutoModeler_Exception');
	}

	public function itShouldThrowValidationExceptionWhenCreatingInvalidModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Insert');

		$model = Mockery::mock('AutoModeler_Model');
		$model->shouldReceive('valid')->andReturn(array('status' => FALSE, 'errors' => array()));
		$model->shouldReceive('state')->andReturn(AutoModeler_Model::STATE_NEW);

		$repository = AutoModeler_Repository_Database::factory($database, 'foos');

		$this->spec(
			function() use ($model, $repository, $qb)
			{
				$validation = Mockery::mock('Validation');
				$validation->shouldReceive('check')->andReturn(FALSE);
				$validation->shouldReceive('errors')->andReturn(array());
				$repository->create($model, NULL, $qb, $validation);
			}
		)->should->throwException('AutoModeler_Exception_Validation');
	}

	public function itShouldUpdateOneRow()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Update');
		$qb->shouldReceive('set')
			->with(Mockery::type('array'))
			->once();
		$qb->shouldReceive('where')
			->with('id', '=', 1)
			->once();
		$qb->shouldReceive('execute')
			->once()
			->andReturn(1);

		$model = new AutoModeler_Model(array('id', 'foo'));
		$model->id = 1;
		$model->state(AutoModeler_Model::STATE_LOADED);

		$repository = AutoModeler_Repository_Database::factory($database, 'foos');

		$count = $repository->update($model, NULL, $qb, $this->default_validation);

		$this->spec($count)->should->be(1);
	}

	public function itShouldThrowExceptionWhenUpdatingNonLoadedModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Update');

		$model = new AutoModeler_Model;
		$repository = AutoModeler_Repository_Database::factory($database, 'foos');

		$this->spec(
			function() use ($model, $repository, $qb)
			{
				$validation = Mockery::mock('Validation');
				$validation->shouldReceive('check')->andReturn(FALSE);
				$validation->shouldReceive('errors')->andReturn(array());
				$repository->update($model, NULL, $qb, $validation);
			}
		)->should->throwException('AutoModeler_Exception');
	}

	public function itShouldThrowValidationExceptionWhenUpdatingInvalidModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Update');

		$model = Mockery::mock('AutoModeler_Model');
		$model->shouldReceive('valid')->andReturn(array('status' => FALSE, 'errors' => array()));
		$model->shouldReceive('state')->andReturn(AutoModeler_Model::STATE_LOADED);

		$repository = AutoModeler_Repository_Database::factory($database, 'foos');

		$this->spec(
			function() use ($model, $repository, $qb)
			{
				$validation = Mockery::mock('Validation');
				$validation->shouldReceive('check')->andReturn(FALSE);
				$validation->shouldReceive('errors')->andReturn(array());
				$repository->update($model, NULL, $qb, $validation);
			}
		)->should->throwException('AutoModeler_Exception_Validation');
	}

	public function itShouldReturnNewModelWhenDeleted()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Delete');
		$qb->shouldReceive('where')
			->with('id', '=', 1)
			->once();
		$qb->shouldReceive('execute')
			->once()
			->andReturn(1);
		
		$model = new AutoModeler_Model(array('id', 'foo'));
		$model->id = 1;
		$model->state(AutoModeler_Model::STATE_LOADED);

		$repository = AutoModeler_Repository_Database::factory($database, 'foos');
		$new_model = $repository->delete($model, $qb);

		$this->spec($new_model)->should->beAnInstanceOf('AutoModeler_Model');
		$this->spec($new_model->state())->should->be(AutoModeler_Model::STATE_NEW);
	}

	public function itShouldThrowExceptionWhenDeletingNonLoadedModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Delete');

		$model = new AutoModeler_Model;
		$repository = AutoModeler_Repository_Database::factory($database, 'foos');

		$this->spec(
			function() use ($model, $repository, $qb)
			{
				$repository->delete($model, $qb);
			}
		)->should->throwException('AutoModeler_Exception');
	}
}