<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use common\traits\AttrTrait;

/**
 * 该model对应数据库表 "lottery".
 *
 * @property integer $id
 * @property string $orderid 订单ID
 * @property string $productid 商品ID
 * @property integer $term 商品期数
 * @property string $userid 用户id
 * @property string $lotteryno 中奖号码
 * @property integer $status 状态
 * @property integer $isused 是否被使用
 * @property integer $islucky 是否中奖
 * @property string $attr 其他信息
 * @property integer $addtime
 * @property integer $modtime
 */
class Lottery extends \yii\db\ActiveRecord
{
    use AttrTrait;

    const IS_USED_TRUE = 1;
    const IS_USED_FALSE = 0;

    const IS_LUCKY_TRUE = 1;
    const IS_LUCKY_FALSE = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lottery';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orderid', 'productid', 'term', 'userid', 'status', 'isused', 'islucky', 'addtime', 'modtime'], 'integer'],
            [['lotteryno'], 'string', 'max' => 50],
            [['attr'], 'string', 'max' => 1024]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orderid' => '订单ID',
            'productid' => '商品ID',
            'term' => '商品期数',
            'userid' => '用户id',
            'lotteryno' => '中奖号码',
            'status' => '状态',
            'isused' => '是否被使用',
            'islucky' => '是否中奖',
            'attr' => '其他信息',
            'addtime' => 'Addtime',
            'modtime' => 'Modtime',
        ];
    }

    public function processInfo()
    {
        return [
            'id' => $this->id,
            'productid' => $this->productid,
            'orderid' => $this->orderid,
            'term' => $this->term,
            'protermstatus' => $this->proterm->status,
            'joinednum' => self::getUsedNum($this->productid, $this->term),
            'userid' => $this->userid,
            'username' => $this->user ? $this->user->name : '',
            'mobile' => $this->user ? $this->user->mobile : '',
            'orderinfo' => $this->getOrderInfo(),
            'lotteryno' => $this->lotteryno,
            'islucky' => $this->islucky,
            'isused' => $this->isused,
            'usedate' => date('Y-m-d H:i:s', $this->modtime),
        ];
    }

    public function getOrderInfo()
    {
        if (!$this->order) {
            return [];
        }
        return $this->order->processInfo();

    }

    public static function getUsedNum($productid, $term)
    {
        return self::find()
            ->where(['productid' => $productid, 'term' => $term, 'isused' => self::IS_USED_TRUE])
            ->count();
    }

    public static function getUnusedNum($productid, $term)
    {
        return self::find()
            ->where(['productid' => $productid, 'term' => $term, 'isused' => self::IS_USED_FALSE])
            ->count();
    }

    public static function getUnusedLotterys($productid, $term, $limit = 1000)
    {
        return self::find()
            ->where(['productid' => $productid, 'term' => $term, 'isused' => self::IS_USED_FALSE])
            ->limit($limit)
            ->all();
    }


    public static function getLuckyNum($productid, $term)
    {
        return self::find()
            ->where(['productid' => $productid, 'term' => $term, 'islucky' => self::IS_LUCKY_TRUE])
            ->count();
    }

    public static function isLotteryGenerated($productid, $term)
    {
        return Lottery::find()->where(['productid' => $productid, 'term' => $term])->exists();
    }

    public static function isLotteryAllUsed($productid, $term)
    {
        return !Lottery::find()->where(['productid' => $productid, 'term' => $term, 'isused' => Lottery::IS_USED_FALSE])->exists();
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userid']);
    }

    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'productid']);
    }

    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'orderid']);
    }

    public function getProterm()
    {
        return $this->hasOne(Proterm::className(), ['id' => 'productid'])->onCondition(['term' => $this->term]);
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['addtime', 'modtime'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['modtime'],
                ],
            ],
        ];
    }

}
