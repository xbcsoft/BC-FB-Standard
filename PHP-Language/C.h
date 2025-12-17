struct A {
    short a;
    String b;
};

struct B {
    A aa;
    Bytes bb[1];
};

struct C {
    A aaa[2];
    B bbb[1];
};