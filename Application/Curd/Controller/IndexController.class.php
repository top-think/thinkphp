<?php
// 本类由系统自动生成，仅供测试用途
namespace Curd\Controller;
use Think\Controller;
class IndexController extends Controller {
    
	// 查询数据
    public function index() {
        $Form = M("Form");
        $list = $Form->order('id desc')->select();
        $this->list =  $list;
        $this->display();
    }

    // 写入数据
    public function insert() {
        $Form = D("Form");
        if ($vo = $Form->create()) {
            $list = $Form->add();
            if ($list !== false) {
                $this->success('数据保存成功！',U('Index/index'));
            } else {
                $this->error('数据写入错误！');
            }
        } else {
            $this->error($Form->getError());
        }
    }

    // 更新数据
    public function update() {
        $Form = D("Form");
        if ($vo = $Form->create()) {
            $list = $Form->save();
            if ($list !== false) {
                $this->success('数据更新成功！',U('Index/index'));
            } else {
                $this->error("没有更新任何数据!");
            }
        } else {
            $this->error($Form->getError());
        }
    }

    // 删除数据
    public function delete($id) {
        if (!empty($id)) {
            $Form = M("Form");
            $result = $Form->delete($id);
            if (false !== $result) {
                $this->success('删除成功！');
            } else {
                $this->error('删除出错！');
            }
        } else {
            $this->error('ID错误！');
        }
    }

    // 编辑数据
    public function edit($id) {
        if (!empty($id)) {
            $Form = M("Form");
            $vo = $Form->getById($id);
            if ($vo) {
                $this->vo   =   $vo;
                $this->display();
            } else {
                $this->error('数据不存在！');
            }
        } else {
            $this->error('数据不存在！');
        }
    }
}