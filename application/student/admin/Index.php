<?php
namespace app\student\admin;
 
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
 
/**
 * student 后台控制器
 */
class Index extends Admin
{
    /**
     * 列表页
     */
    public function index()
    {
        $map = $this->getMap();//获取搜索框的值
        // 读取用户数据
        $order = $this->getOrder();
        $data_list = Db::name('student_info')->where($map)->order($order)->paginate();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        ->setPageTitle('学生信息列表')
        ->setPageTips('这是页面提示信息', 'danger')
        ->addColumns([ // 批量添加列
            ['id', 'ID'],
            ['name', '姓名'],
            ['sex','性别 ',['0'=>'女','1'=>'男','2'=>'保密'] ],
            ['age', '年龄'],
            ['school', '学校'],
            ['province', '省'],
            ['city', '市'],
            ['county', '县'],
            ['create_time', '创建时间', 'datetime'],
            ['update_time', '修改时间', 'datetime'],
        ])
        ->addOrder('id,name,age') // 添加排序
        ->addColumn('right_button', '操作', 'btn')
        ->addRightButton('edit',['href' => url('edit', ['id' => '__id__'])], ['skin' => 'layui-layer-lan'])
        ->addRightButton('delete',['href' => url('delete', ['id' => '__id__'])])
        ->addTopButton('add', [], ['skin' => 'layui-layer-lan'])
        ->setRowList($data_list)
        ->setColumnWidth([
            'id'  => 50,
            'name' => 80,
            'sex' => 50,
            'age' => 50,
            'age' => 50,
            'school' => 100
        ])
        ->setSearch(['id' => 'ID', 'name' => '姓名', 'province' => '省']) // 设置搜索参数
        ->fetch();
    }
 
    /**
     * 添加学生信息
     */
    public function add()
    {
        // 使用ZBuilder构建表单页面，并将页面标题设置为“添加”
        $list_province =  Db::name('address')->where('type',1)->column('id,name');
        $list_interest =  ['ll' => '語言學習', 'ma' => '管理', 'tech' => '科技應用'];
        return ZBuilder::make('form')
            ->setPageTitle('添加学生基本信息')
            ->addText('name', '姓名')
            ->addRadio('sex', '性别', '', ['0' => '女', '1' => '男', '2' => '保密'],'2')
            ->addDate('birthday', '生日', '', '', 'yyyy-mm-dd')
            ->addText('school', '学校')
            ->setTrigger('school','department')
            ->addLinkage('province', '省', '', $list_province, '', url('get_city'), 'city,county')
            ->addLinkage('city', '市', '', '', '', url('get_county'), 'county')
            ->addSelect('county', '县')
            ->setUrl(url('save'))
            ->addCheckbox('interest', '有興趣的課程', '',$list_interest, 'gz,sz', ['color' => 'danger', 'size'=>'square'])
            ->addBtn('<button type="button" class="btn btn-rounded btn-warning">额外按钮1</button>')
            ->addBtn('<button type="button" class="btn btn-rounded btn-danger">额外按钮2</button>')
            ->setBtnTitle(['submit' => '确定', 'back' => '返回前一页'])
            ->fetch();
    }
 
    /**
     * 保存 添加的信息
     */
    public function save()
    {
        if(request()->isPost()){
            $post = request()->post();
            $data['name'] = $post['name'];
            $data['sex'] = $post['sex'];
            $data['school'] = $post['school'];
            $data['birthday'] = strtotime($post['birthday']);
            $data['age'] = date('Y',time())-date('Y',$data['birthday']);
            $data['province'] = $this->get_where($post['province']);
            $data['city']     = $this->get_where($post['city']);
            $data['county']   = $this->get_where($post['county']);
            $data['create_time']=time();
            $data['update_time']=time();
            $data['interest'] = implode(',', $post['interest']);
            $res = Db::name('student_info')->insert($data);
            $arr['code'] = '1'; //判断状态
            $arr['msg'] = '请求成功'; //回传信息
            $arr['list'] = $res;
            return $arr;
        }
    }
 
     /**
     * 编辑页面
     */
 
    public function edit($id='')
    {
        if(empty($id)){
            return ZBuilder::make('form')->assign('empty_tips', '请指定修改对象')->hideBtn(['submit', 'back'])->fetch();
        }
        $data = Db::name('student_info')->find($id);//获取编辑的数据
 
        $list_province =  Db::name('address')->where('type',1)->column('id,name');//省份
        //选出来省id
        $pid  =  Db::name('address')->where('name', $data['province'])->find()['id'];
        $list_city = Db::name('address')->where('pid',$pid)->column('id,name');
 
        //选出来市id
        $pid  =  Db::name('address')->where('name', $data['city'])->find()['id'];
        $list_county = Db::name('address')->where('pid',$pid)->column('id,name');
 
        //这里需要知道每个人的地点的id，才可以在编辑的时候显示出来
        $data['province'] = Db::name('address')->where('name', $data['province'])->find()['id'];
        $data['city'] = Db::name('address')->where('name', $data['city'])->find()['id'];
        $data['county'] = Db::name('address')->where('name', $data['county'])->find()['id'];

        return ZBuilder::make('form')
        ->setPageTitle('修改学生基本信息')
        ->addText('name', '姓名')
        ->addRadio('sex', '性别', '', ['0' => '女', '1' => '男', '2' => '保密'],'2')
        ->addDate('birthday', '生日', '', '', 'yyyy-mm-dd')
        ->addText('school', '学校')
        ->addLinkage('province', '省', '',$list_province, '', url('get_city'), 'city,county')
        ->addLinkage('city', '市', '', $list_city,'', url('get_county'), 'county')
        ->addSelect('county', '县','',$list_county)
        ->setFormData($data)
        ->setUrl(url('editSave', ['id' =>$id]))
        ->fetch();
    }
 
 
      /**
     * 修改保存
     */
    public function editSave(){
        $post = request()->post();
        $data['id'] = request()->param('id');
        $data['name'] = $post['name'];
        $data['sex'] = $post['sex'];
        $data['school'] = $post['school'];
        $data['birthday'] = strtotime($post['birthday']);
        $data['age'] = date('Y',time())-date('Y',$data['birthday']);
        $data['province'] = $this->get_where($post['province']);
        $data['city']     = $this->get_where($post['city']);
        $data['county']   = $this->get_where($post['county']);
        $data['update_time']=time();
 
        $res = Db::table('dp_student_info')->update($data);
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息
        $arr['list'] = $res;
        return $arr;
    }
    
     /**
     * 删除信息
     *      $id 某个学生id
     */
    public function delete($id='')
    {
        $res = Db::name('student_info')->delete($id);
        if($res){
            $arr['code'] = '1'; //判断状态
            $arr['msg'] = '请求成功'; //回传信息
            $arr['list'] = $res;
        }else{
            $arr['code'] = '404'; //判断状态
            $arr['msg'] = '请求失败'; //回传信息
            $arr['list'] = $res;
        }
        return json($arr);
    }
 
     /**
     * 获取地点
     */
    public function get_where($id){
        $data = Db::name('address')->find($id);
        return $data['name']??$id;
    }
 
    /**
     * 获取市 二维数组
     */
    public function get_city($province = '')
    {
        
        $data = Db::name('address')->where('pid',$province)->field(['id'=>'key','name'=>'value'])->select();
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息
        $arr['list'] = $data;
        return json($arr);
    }
 
    /**
     *获取县 二维数组
     */
    public function get_county($city = '')
    {
        $data = Db::name('address')->where('pid',$city)->field(['id'=>'key','name'=>'value'])->select();
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息
        $arr['list'] = $data;
        return json($arr);
    }
 
}