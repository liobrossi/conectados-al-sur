<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * Instances Controller
 *
 * @property \App\Model\Table\InstancesTable $Instances
 */
class InstancesController extends AppController
{

    /**
     * Index method
     */
    public function index()
    {
        $query = $this->Instances
            ->find()
            ->select(['id', 'name', 'namespace', 'logo']);
        $instances = $this->paginate($query);

        $this->set(compact('instances'));
        // $this->set('_serialize', ['instances']);
    }


    /**
     * Preview method
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function preview($instance_namespace = null)
    {
        # load instance data
        $instance = $this->Instances
            ->find()
            ->select(['id', 'name', 'description', 'description_es', 'namespace', 'logo'])
            ->where(['Instances.namespace' => $instance_namespace])
            ->first();

        $this->set('instance', $instance);
        $this->set('instance_namespace', $instance_namespace);
        $this->set('instance_logo', $instance->logo);
        // $this->set('_serialize', ['instance']);
    }

    /**
     * Graph method
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function dots($instance_namespace = null)
    {
        $instance = $this->Instances
            ->find()
            ->select(['id', 'name', 'namespace', 'logo'])
            ->where(['Instances.namespace' => $instance_namespace])
            ->contain([])
            ->first();

        $this->set('instance', $instance);
        $this->set('instance_namespace', $instance_namespace);
        $this->set('instance_logo', $instance->logo);
        // $this->set(compact('instance', 'instance_namespace'));
        // $this->set('_serialize', ['instance']);
    }

    /**
     * Map method
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function map($instance_namespace = null)
    {
        // ----- instance independent data --------

        // location data
        // available continents
        $continents = TableRegistry::get('Continents')
            ->find()
            ->where(['Continents.id !=' => '0'])
            ->all();
        // var_dump($continents);

        // available subcontinents
        $subcontinents = TableRegistry::get('Subcontinents')
            ->find()
            ->where(['Subcontinents.id !=' => '0'])
            ->all();
        // var_dump($subcontinents);

        // available countries
        $countries = TableRegistry::get('Countries')
            ->find()
            ->where(['Countries.id !=' => '0'])
            ->all();
        // var_dump($countries);

        // available genres
        $genres = TableRegistry::get('Genres')
            ->find()
            ->where(['Genres.name !=' => '[unused]'])
            ->all();

        // available project_stages
        $project_stages = TableRegistry::get('ProjectStages')
            ->find()
            ->where(['ProjectStages.name !=' => '[unused]'])
            ->all();

        // var_dump($genres);
        // var_dump($project_stages);


        // ----- instance dependent data --------
        // instance data
        $instance = $this->Instances
            ->find()
            ->where(['Instances.namespace' => $instance_namespace])
            ->contain([
                'OrganizationTypes' => function ($q) {
                   return $q->where(['OrganizationTypes.name !=' => '[unused]']);
                },
                'Categories' => function ($q) {
                   return $q->where(['Categories.name !=' => '[unused]']);
                }
            ])
            ->first();

        $_categories = $this->Instances->Categories
            ->find('list')
            ->where(['Categories.name !=' => '[unused]'])
            ->order(['name' =>'ASC'])
            ->all();

        $_organization_types = $this->Instances->OrganizationTypes
            ->find('list')
            ->where(['OrganizationTypes.name !=' => '[unused]'])
            ->order(['name' =>'ASC'])
            ->all();
        // available categories
        // var_dump($instance->categories);

        $projects = TableRegistry::get('Projects')
            ->find()
            ->where(['Projects.instance_id' => $instance->id])
            ->select([
                    'id', 'name', 'user_id', 'instance_id', 'description',
                     'organization', 'organization_type_id', 'project_stage_id',
                     'country_id', 'city_id', 'latitude', 'longitude', 'created',
                     'modified', 'start_date', 'finish_date'
                ])
            ->contain([
                    'Users' => function ($q) {
                       return $q->select(['Users.genre_id']);
                    },
                    'Categories' => function ($q) {
                        return $q->select(['Categories.id']);
                    },
                ])
            ->all();
        // ->first();
        // var_dump($projects->categories);

        // independent data
        $this->set('genres', $genres);
        $this->set('project_stages', $project_stages);

        // instance data
        $this->set('instance_namespace', $instance_namespace);
        $this->set('instance_logo', $instance->logo);
        $this->set('instance', $instance);
        $this->set('projects', $projects);
        $this->set('continents', $continents);
        $this->set('subcontinents', $subcontinents);
        $this->set('countries', $countries);
        // $this->set(compact('projects', 'instance_namespace'));
        // $this->set('_serialize', ['projects']);
        $this->set('_organization_types', $_organization_types);
        $this->set('_categories', $_categories);
    }



    /**
     * View method
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($instance_namespace = null)
    {
        $instance = $this->Instances
            ->find()
            ->contain([
                'OrganizationTypes' => function ($q) {
                   return $q->where(['OrganizationTypes.name !=' => '[unused]']);
                },
                'Categories' => function ($q) {
                   return $q->where(['Categories.name !=' => '[unused]']);
                }
            ])
            ->where(['Instances.namespace' => $instance_namespace])
            ->first();

        $this->set('instance', $instance);
        $this->set('instance_namespace', $instance_namespace);
        $this->set('instance_logo', $instance->logo);
        // $this->set('_serialize', ['instance']);
    }

    /**
     * Add method
     * Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $instance = $this->Instances->newEntity();
        if ($this->request->is('post')) {

            # NO ES ATÓMICO!
            $last_id = $this->Instances
                ->find()
                ->select(['id'])
                ->order(['id' =>'DESC'])
                ->first()->id;
            #var_dump($last_id);

            $instance = $this->Instances->patchEntity($instance, $this->request->data);
            $instance->id = $last_id + 1;
            if ($this->Instances->save($instance)) {
                $this->Flash->success(__('The instance has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The instance could not be saved. Please, try again.'));
                return $this->redirect(['action' => 'index']);
            }
        }
        $this->set(compact('instance'));
        $this->set('_serialize', ['instance']);
    }

    /**
     * Edit method
     * Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($instance_namespace = null)
    {
        $instance = $this->Instances
            ->find()
            ->where(['Instances.namespace' => $instance_namespace])
            ->contain([])
            ->first();

        if ($this->request->is(['patch', 'post', 'put'])) {
            
            // do not remove logo if not provided!
            if ( isset($this->request->data['logo']) 
                && isset($this->request->data['logo']['name']) 
                && empty($this->request->data['logo']['name']) ) {
                unset($this->request->data['logo']);
            }
            $instance = $this->Instances->patchEntity($instance, $this->request->data);

            if ($this->Instances->save($instance)) {
                $this->Flash->success(__('The instance has been saved.'));
                return $this->redirect(['action' => 'view', $instance->namespace]);
            } else {
                $this->Flash->error(__('The instance could not be saved. Please, try again.'));
                return $this->redirect(['action' => 'view', $instance->namespace]);
            }
        }
        $this->set(compact('instance'));
        $this->set('instance_namespace', $instance_namespace);
        $this->set('instance_logo', $instance->logo);
        $this->set('_serialize', ['instance']);
    }

    /**
     * Delete method
     * Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($instance_namespace = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $instance = $this->Instances
            ->find()
            ->where(['Instances.namespace' => $instance_namespace])
            ->contain([])
            ->first();
        
        if ($this->Instances->delete($instance)) {
             $this->Flash->success(__('The instance "{0}" has been deleted.', $instance->name));
        } else {
            $this->Flash->error(__('The instance could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'index']);
    }
}
