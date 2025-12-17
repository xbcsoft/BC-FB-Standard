struct 用户信息 {
	String 姓名;
	int 年龄;
	float 身高[3];
};

struct 订单 {
	用户信息 买家;
	String 商品名称;
	int 数量;
};