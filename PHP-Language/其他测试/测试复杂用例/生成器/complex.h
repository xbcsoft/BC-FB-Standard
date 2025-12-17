// 基础信息结构体
struct BasicInfo {
    char gender;           // 性别（用char表示）
    short age;            // 年龄
    int id;              // ID号
    int64 timestamp;     // 时间戳
    float height;        // 身高
    double weight;       // 体重
    String name;         // 姓名
    Bytes avatar;        // 头像数据
    bool isVIP;          // 是否VIP
};

// 联系方式结构体
struct Contact {
    String phone;        // 电话
    String email;        // 邮箱
    String address[3];   // 多个地址
};

// 商品结构体
struct Product {
    String id;           // 商品ID
    String name;         // 商品名称
    double price;        // 价格
    int stock;          // 库存
    Bytes image[5];     // 商品图片
    bool isOnSale;      // 是否在售
};

// 订单项结构体
struct OrderItem {
    Product product;     // 商品信息
    int quantity;       // 数量
    double unitPrice;   // 单价
    short discount;     // 折扣
};

// 支付信息结构体
struct Payment {
    String id;          // 支付ID
    uchar type;         // 支付类型
    double amount;      // 支付金额
    String currency;    // 货币类型
    int64 timestamp;    // 支付时间
};

// 完整订单结构体
struct ComplexOrder {
    String orderId;                 // 订单ID
    BasicInfo customerInfo;         // 客户基本信息
    Contact contactInfo;            // 联系方式
    OrderItem items[10];           // 订单项（最多10个）
    Payment payments[3];           // 支付记录（最多3次）
    uint status;                   // 订单状态
    int64 createTime;              // 创建时间
    int64 updateTime;              // 更新时间
    String remarks[5];             // 备注信息
    bool isPaid;                   // 是否已支付
    float totalWeight;             // 总重量
    ushort itemCount;              // 商品总数
};
