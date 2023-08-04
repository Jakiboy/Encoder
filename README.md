# Encoder

PHP String Encoding Helper (UTF-8) to fix mixed encoded strings.

## 🔧 Installing:

#### Using Composer:

```
composer require jakiboy/encoder
```

## 💡 Example:

### Basic:

```php
use Encoding\Encoder;

$string = 'MonÃ©tius nÃ´s tumore Ã®nusitÃ to quàdam et novo ut rebÃ¨llis';
echo (new Encoder())->sanitize($string);
```
```
Monétius nôs tumore înusitàto quàdam et novo ut rebèllis
```
### Advanced: Iconv (TRANSLIT)

```php
use Encoding\Encoder;

$string = 'Монётиусę ėįšнôс тумореųūž îнуситàто qуодам ет ново ут ребэллис';
echo (new Encoder(true, 'TRANSLIT'))->sanitize($string);
```
```
Монётиусę ėįšнôс тумореųūž îнуситàто qуодам ет ново ут ребэллис
```

### Advanced: Iconv (IGNORE)

```php
use Encoding\Encoder;

$string = 'Монётиусę ėįšнôс тумореųūž îнуситàто qуодам ет ново ут ребэллис';
echo (new Encoder(true, 'IGNORE'))->sanitize($string);
```
```
ô îà q
```

## Authors:

* **Jihad Sinnaour** - [Jakiboy](https://github.com/Jakiboy) (*Initial work*)

See also the full list of [contributors](https://github.com/Jakiboy/Encoder/contributors) who participated in this project. Any suggestions (Pull requests) are welcome!

## License:

This project is licensed under the MIT License - see the [LICENSE](https://github.com/Jakiboy/Encoder/blob/master/LICENSE) file for details.

## ⭐ Support:

Please give it a Star if you like the project.