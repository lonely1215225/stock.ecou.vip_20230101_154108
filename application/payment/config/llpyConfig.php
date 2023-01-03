<?php
namespace app\payment\config;
/* *
 * 配置文件
 * 版本：1.2
 * 日期：2014-06-13
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */

//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//商户编号是商户在连连钱包支付平台上开设的商户号码，为18位数字，如：201408071000001543
class llpyConfig
{

    public static function getinfo()
    {

        return [

            'oid_partner'          => '201810310002257003',
            //秘钥格式注意不能修改（左对齐，右边有回车符）
            'RSA_PRIVATE_KEY'      => '-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEAzJfCrHX/jcWTAt/+4Sc7Uq+o2vZ2HlEbHJaskPx4HcGaDRJ/
47bzcbtivzdIh7Ai5XqBYXS9c1v1KrMGVV2kWmZ6gZjhvUhNxRAnMF9qcjQX8Q6C
qeGhLkHrTKe9wzLX58OnZki5/AuQIhU4IPFbQUoJimlTvRYfF63fklfpGoZrHNnD
xmi0IwJHehmIh2mIEd2as5YZJpkYv4Hb7Ls8PdzwtPh426KxR+2j0DrqRA9YHweW
tALZH/LR/glWpjRO4Doq3DzdBgbySo8vKlcEhdt6VzRWtVb8utOr+FIxprb7Uahb
Cr52/ACZihtRaQBiIV78+NGpTHXH1Cs/9kacwwIDAQABAoIBAQCdiCUPYfQaF40w
44R/nROigTsFDky43z5+7s2E/cEHOHEDq8Dpd49PfTd1gH3KbKcSBxfkEF1nm+DU
tfAkfuvz54BglXULp8ap+9wQ9QcjVFUy+TdG00KmOX+SEH6e9GEmRZJ5wXnjRov+
klQbmXvoc7eQ6bTenU/njaveJO6JyqHn6bIHL0Hnn33/epNM+sJKMddQjd8DgUda
FTeEKDFhJmIC4iNoY+ih1LsE7/ODHwCeURNLsRRzVLP7eCeGEFlq3rA1Y9hy8lzH
3WpHhjLxKWYb97PcYmZSOpgLwMtkCqxY/cilnkUo3LUVIEb2iLT319N+drmG66AK
kyVR/MnBAoGBAOiTG0rnnw0DHXXCUPaZs5x4d0dOvRd+0WGyC3BjyQs6ARAJTCk3
sjJihTu5kCQJPUvULThACvvx3wRimbfKVxl4yXNT3vCgwCMCMV/+HFvA1yDy3/jM
Xg/oY6yjewJdilnZgnqyoMVZhobNk479gYDAOLIAyYHxk1aFMtM7MH2TAoGBAOEz
JdephgXGQ9IJo/fzi17i3jdgza95VEGsgyU+soF9AZ2jV55u+Cainkn8freQIKb+
5lVWeJ6BKL0EZfTOffK3e8fwUzhOSo4XhXr5dHMuK8VkTNGjQZQrynW/G60diPWq
I0p3g5QdDeoaADSCpqqWQtufWj9AUNll2BwFlGIRAoGARk0BOPkeiK5iX1AnbQM0
2Z1IYNOaMNnyrJdHAegHw077Nz+4N9VQFg7VuyHyQhJQ5vTx3kjtiQ6pnQe482dE
QLzUF/pIL3BH480r45pKNCnsXVdNAEW2QRS73FlmO2bPBS3MVQ5dronLMkA91EEo
viRcfuHiB1dgdAy7OkJv++UCgYEAyYAKjD2TPfd5F6aooGO6gNxeGQ70+92EFn9V
mS3Qayx/FZ3h+FEymN9I4sqaV4UOsl3BmvoUUz8eRIJ69+ELsPGcP/o5fFgRPbKu
LKqoF6doYuasFagONZY5QcIN8YhL1AS4LxlhElYs7Rr2tAVzO4/Xosui5JWXwe3u
wKP9cQECgYEAodd73VuBcnOI0KehTYE0vbb4yQ0jdfaCDaBm/n59BFiu2BKqq/d3
tUHhm5xlW6F42639Te6yuIzg9ihkGt3uU8uVksWWdJFlxVNGcjASYuzT21uuulpF
wVaz15TKcXSrFzvoKhsTHqv9thOY83U4SJuy6NV4RGy0UL4aZgZsRZc=
-----END RSA PRIVATE KEY-----',
            'LIANLIAN_PUBLICK_KEY' => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCSS/DiwdCf/aZsxxcacDnooGph3d2JOj5GXWi+q3gznZauZjkNP8SKl3J2liP0O6rU/Y/29+IUe+GTMhMOFJuZm1htAtKiu5ekW0GlBMWxf4FPkYlQkPE0FtaoMP3gYfh+OwI+fIRrpW3ySn3mScnc6Z700nU/VYrRkfcSCbSnRwIDAQAB
-----END PUBLIC KEY-----',
            //安全检验码，以数字和字母组成的字符
            'key'                  => '201810310002257003_sahdisa_2018103',

            //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

            //版本号
            'version'              => '1.2',

            //请求应用标识 为wap版本，不需修改
            'app_request'          => '3',


            //签名方式 不需修改
            'sign_type'            => strtoupper('RSA'),

            //订单有效时间  分钟为单位，默认为10080分钟（7天）
            'valid_order'          => "10080",

            //字符编码格式 目前支持 gbk 或 utf-8
            'input_charset'        => strtolower('utf-8'),

            //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
            'transport'            => 'http',
        ];
    }
}