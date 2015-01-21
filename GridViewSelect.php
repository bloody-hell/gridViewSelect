<?php


namespace bloody_hell\gridViewSelect;


use yii\bootstrap\Button;
use yii\db\ActiveRecord;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\i18n\PhpMessageSource;
use yii\jui\Dialog;
use yii\jui\InputWidget;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

class GridViewSelect extends InputWidget
{
    public $gridClass;

    public $gridOptions = [];

    public $valueLabel;

    /**
     * @var
     */
    public $labelExpression;

    public $linkExpression;

    public $keyFunction;

    public $pjaxOptions = [];

    public $dialogTitle;

    public $buttonsContainer = [
        'tagName' => 'div',
    ];

    /**
     * @var ActiveForm
     */
    public $form;

    public function init()
    {
        parent::init();

        if(!isset(\Yii::$app->i18n->translations['grid-view-select'])){
            \Yii::$app->i18n->translations['grid-view-select'] = [
                'class' => PhpMessageSource::className(),
                'basePath' => '@vendor/bloody-hell/yii2-gridView-select/messages',
            ];
        }

        if(!$this->keyFunction){
            $this->keyFunction = function(ActiveRecord $item){return $item->getPrimaryKey();};
        }

        $this->gridOptions['class'] = $this->gridClass ? : GridView::className();

        if($this->hasModel()){
            $this->value = $this->model->{$this->attribute};
        }

        if(isset($this->options['id']) && !$this->getId(false)){
            $this->setId($this->options['id']);
        }

        if(!$this->getId() && $this->hasModel()){
            $this->setId(Html::getInputId($this->model, $this->attribute));
        }

        $this->gridOptions['id'] = $this->getId() . '_grid';

        $this->gridOptions['columns'][] = [
            'class'     => ActionColumn::className(),
            'buttons'   => [
                'select'    => function($url, $model){
                    $value = call_user_func($this->keyFunction, $model);
                    $label = $this->labelExpression ? call_user_func($this->labelExpression, $model) : null;
                    $url = $this->linkExpression ? call_user_func($this->linkExpression, $model) : '#';
                    return $this->hasModel() ?
                        Html::activeRadio($this->model, $this->attribute, [
                            'uncheck'    => null,
                            'value'      => $value,
                            'id'         => $this->getId().'_'.$value,
                            'data-label' => $label,
                            'data-url'   => $url,
                        ]) :
                        Html::radio($this->name, $value == $this->value, [
                            'value'      => $value,
                            'id'         => $this->getId().'_'.$value,
                            'data-label' => $label,
                            'data-url'   => $url,
                        ]);
                },
            ],
            'template'  => '{select}',
            'contentOptions'    => ['class' => $this->getActionColumnCssClass(),],
        ];

        if($this->hasModel() && $this->form){
            $this->form->attributes[$this->attribute]['input'] = '[name="'.Html::getInputName($this->model, $this->attribute).'"]:checked';
        }
    }


    public function run()
    {
        echo Html::beginTag('div', $this->options);
//        echo Html::radio('', false, ['style' => 'display: none;']); // Костыль для JS валидации
        echo Html::tag('strong', $this->valueLabel ? $this->valueLabel : $this->value, []);

        echo $this->hasModel() ?
            Html::activeHiddenInput($this->model, $this->attribute, ['id' => $this->getHiddenInputId(),]) :
            Html::hiddenInput($this->name, $this->value, ['id' => $this->getHiddenInputId(),]);

        $dialog = Dialog::begin([
            'id'            => $this->getDialogId(),
            'clientOptions' => [
                'autoOpen' => false,
                'width' => new JsExpression('$(\'.container\').first().innerWidth() - 50'),
                'draggable' => false,
                'title'     => $this->dialogTitle,
                'closeOnEscapeType' => true,
            ],
        ]);

        echo $this->renderButtonsContainer();

        $pjax = Pjax::begin([]);

        echo \Yii::createObject($this->gridOptions)->run();

        $pjax->end();

        $dialog->end();
        echo '&nbsp;&nbsp;';
        echo Html::a('Выбрать...', '#', ['id' => $this->getLinkId()]);
        echo Html::endTag('div');

        $this->view->registerJs('
$(\'#'.$this->getLinkId().'\').on(\'click\', function(){
    var $dialog = $(\'#'.$this->getDialogId().'\');
    $dialog.dialog(\'open\');
    return false;
});
        ');
    }

    protected function renderButtonsContainer()
    {
        $options = $this->buttonsContainer;

        $tag = ArrayHelper::remove($options, 'tagName', 'div');

        return Html::tag($tag, implode(PHP_EOL, [
            Button::widget([
                'label'  => 'Выбрать',
                'options'=> [
                    'class'     => 'btn-success',
                    'onclick'   => new JsExpression('
    var $this = $(\'#'.$this->getDialogId().'\'),
        $input = $this.find(\'.'.$this->getActionColumnCssClass().' input:checked\');

    if($input.length > 0){
        $(\'#'.$this->getHiddenInputId().'\').val($input.val()).siblings(\'strong\').find(\'a\').attr(\'href\', $input.data(\'url\')).text($input.data(\'label\'));
    }

    $this.dialog(\'close\');
    return false;
'),
                ],
            ]),

            Button::widget([
                'label'  => 'Отмена',
                'options'=> [
                    'class'     => 'btn-danger',
                    'onclick'   => new JsExpression('$(\'#'.$this->getDialogId().'\').dialog(\'close\'); return false;'),
                ],
            ]),
        ]), $options);
    }

    protected function getLinkId()
    {
        return $this->getId() . '_select';
    }

    protected function getDialogId()
    {
        return $this->getId() . '_dialog';
    }

    protected function getActionColumnCssClass()
    {
        return $this->getId() . '_td';
    }

    protected function getHiddenInputId()
    {
        return $this->getId() . '_input';
    }
} 