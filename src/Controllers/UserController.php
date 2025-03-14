<?php

namespace Dcat\Admin\Controllers;

use Dcat\Admin\Auth\Permission;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\IFrameGrid;
use Dcat\Admin\Models\Administrator as AdministratorModel;
use Dcat\Admin\Models\Repositories\Administrator;
use Dcat\Admin\Show;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\Tree;

class UserController extends AdminController
{
    /**
     * Get content title.
     *
     * @return string
     */
    public function title()
    {
        return trans('admin.administrator');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Administrator('roles'), function (Grid $grid) {
            $grid->id('ID')->sortable();
            $grid->username;
            $grid->name;
            $grid->roles->pluck('name')->label('primary', 3);

            $permissionModel = config('admin.database.permissions_model');
            $roleModel = config('admin.database.roles_model');
            $nodes = (new $permissionModel())->allNodes();
            $grid->permissions
                ->if(function () {
                    return ! empty($this->roles);
                })
                ->showTreeInDialog(function (Grid\Displayers\DialogTree $tree) use (&$nodes, $roleModel) {
                    $tree->nodes($nodes);

                    foreach (array_column($this->roles, 'slug') as $slug) {
                        if ($roleModel::isAdministrator($slug)) {
                            $tree->checkAll();
                        }
                    }
                })
                ->else()
                ->emptyString();

            $grid->created_at;
            $grid->updated_at->sortable();

            $grid->quickSearch(['id', 'name', 'username']);

            $grid->disableBatchDelete();
            $grid->showQuickEditButton();
            $grid->disableFilterButton();
            $grid->enableDialogCreate();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                if ($actions->getKey() == AdministratorModel::DEFAULT_ID) {
                    $actions->disableDelete();
                }
            });
        });
    }

    /**
     * @return IFrameGrid
     */
    protected function iFrameGrid()
    {
        $grid = new IFrameGrid(new Administrator());

        $grid->quickSearch(['id', 'name', 'username']);

        $grid->id->sortable();
        $grid->username;
        $grid->name;
        $grid->created_at;

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Administrator('roles'), function (Show $show) {
            $show->id;
            $show->username;
            $show->name;

            $show->avatar(__('admin.avatar'))->image();

            $show->roles->as(function ($roles) {
                if (! $roles) {
                    return;
                }

                return collect($roles)->pluck('name');
            })->label();

            $show->permissions->unescape()->as(function () {
                $roles = (array) $this->roles;

                $permissionModel = config('admin.database.permissions_model');
                $roleModel = config('admin.database.roles_model');
                $permissionModel = new $permissionModel();
                $nodes = $permissionModel->allNodes();

                $tree = Tree::make($nodes);

                $isAdministrator = false;
                foreach (array_column($roles, 'slug') as $slug) {
                    if ($roleModel::isAdministrator($slug)) {
                        $tree->checkAll();
                        $isAdministrator = true;
                    }
                }

                if (! $isAdministrator) {
                    $keyName = $permissionModel->getKeyName();
                    $tree->check(
                        $roleModel::getPermissionId(array_column($roles, $keyName))->flatten()
                    );
                }

                return $tree->render();
            });

            $show->created_at;
            $show->updated_at;
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        return Form::make(new Administrator('roles'), function (Form $form) {
            $userTable = config('admin.database.users_table');

            $connection = config('admin.database.connection');

            $id = $form->getKey();

            $form->display('id', 'ID');

            $form->text('username', trans('admin.username'))
                ->required()
                ->creationRules(['required', "unique:{$connection}.{$userTable}"])
                ->updateRules(['required', "unique:{$connection}.{$userTable},username,$id"]);
            $form->text('name', trans('admin.name'))->required();
            $form->image('avatar', trans('admin.avatar'));

            if ($id) {
                $form->password('password', trans('admin.password'))
                    ->minLength(5)
                    ->maxLength(20)
                    ->customFormat(function () {
                        return '';
                    });
            } else {
                $form->password('password', trans('admin.password'))
                    ->required()
                    ->minLength(5)
                    ->maxLength(20);
            }

            $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

            $form->ignore(['password_confirmation']);

            $form->multipleSelect('roles', trans('admin.roles'))
                ->options(function () {
                    $roleModel = config('admin.database.roles_model');

                    return $roleModel::all()->pluck('name', 'id');
                })
                ->customFormat(function ($v) {
                    return array_column($v, 'id');
                });

            $form->display('created_at', trans('admin.created_at'));
            $form->display('updated_at', trans('admin.updated_at'));

            if ($id == AdministratorModel::DEFAULT_ID) {
                $form->disableDeleteButton();
            }
        })->saving(function (Form $form) {
            if ($form->password && $form->model()->get('password') != $form->password) {
                $form->password = bcrypt($form->password);
            }

            if (! $form->password) {
                $form->deleteInput('password');
            }
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (in_array(AdministratorModel::DEFAULT_ID, Helper::array($id))) {
            Permission::error();
        }

        return parent::destroy($id);
    }
}
