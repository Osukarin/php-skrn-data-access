# php-skrn-data-access

Es una libreria que permite la coneccion a una base de datos y ejecutar transacciones y consultas, permite mapear automáticamente las tablas de una base de datos a un objeto y realizar operaciones transaccionales.

# Diseño de base de datos

Para utilizar al máximo las capacidades de este API, es necesario apegarse a ciertos estándares que se describen a continuación:

* Una tabla que utiliza una llave primaria autoincremental debe llevar como nombre "id"
* Si una tabla tiene una llave compuesta, deben estar marcadas como llaves primarias o tener un índice tipo UNIQUE

Para la ejemplificación del uso del API se usará la tabla


```sql
CREATE TABLE `persona` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) NOT NULL,
  `apellido` varchar(45) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `estatura` decimal(2,2) DEFAULT NULL,
  `estado` varchar(1) DEFAULT NULL
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

# Configuración

Se debe configurar los parámetros de la base de datos para poder acceder a ella.

## Transacciones

Se debe crear una clase que extienda del objeto MyBDItem

```php
<?php

require_once 'MyBDItem';

class TItem extends MyBDItem{

    public function setConnection(){
        $this->__host = "localhost";
        $this->__user = "usuario";
        $this->__pass = "password";
        $this->__db = "base_de_datos";
        $this->__charset = "utf8";
    }
}

```

Dentro de ella se configuran los parámetros mostrados en el ejemplo anterior.

### Insertar

Para insertar un registro se crea un objeto TItem y se envía la tabla sobre la cual se hará la operación.

```php
<?php
$item = new TItem('persona');
```

Se asignan valores a atributos del objeto, hay que recordar que en PHP se pueden crear atributos dinámicamente, no es necesario declararlos dentro del objeto.

```php
<?php
$item = new TItem('persona');
$item->nombre = "Fulano";
$item->apellido = "Ramirez";
$item->fecha_nacimiento = "1985-09-02";
```

Una vez llenados los campos, se ejecuta el método para insertar

```php
<?php
$item = new TItem('persona');
$item->nombre = "Fulano";
$item->apellido = "Ramirez";
$item->fecha_nacimiento = "1985-09-02";
$item->insert();
```

Otro modo de insertar es enviando un arreglo con la información

```php
<?php

$datos = ["nombre" => "Fulano", 
"apellido" => "Ramirez",
"fecha_nacimiento" => "1985-09-02"];

$item = new TItem('persona');
$item->insert($datos);
```

Esto es particularmente útil cuando se reciben datos por medio de los request GET y POST, ya que por ser arreglos, se pueden enviar directamente al objeto transaccional:

```php
<?php

$item = new TItem('persona');
$item->insert($_GET);
```

En este caso, como es una tabla con una llave primaria que se autoincrementa, el método insert regresa el identificador generado por el autoincrement, solamente se asigna a una variable y ya se tiene disponible el id generado asociado al registro

```php
<?php

$item = new TItem('persona');
$id = $item->insert($_GET);
```

Si fuera una tabla que no tiene llave primaria autoincremental, el método insert retorna true si se insertó correctamente.

### Actualizar

Para actualizar un registro se crea un objeto TItem y se envía la tabla sobre la cual se hará la operación.

```php
<?php
$item = new TItem('persona');
```

Se asignan valores a atributos del objeto, hay que recordar que en PHP se pueden crear atributos dinámicamente, no es necesario declararlos dentro del objeto. A diferencia del insert, se debe enviár la llave primaria dentro de los parámetros, asi se puede saber que registro se quiere actualizar.

```php
<?php
$item = new TItem('persona');
$item->id = 1;
$item->nombre = "Mengano";
$item->apellido = "Ramirez";
$item->fecha_nacimiento = "1985-09-02";
```

Una vez llenados los campos, se ejecuta el método para actualizar

```php
<?php
$item = new TItem('persona');
$item->id = 1;
$item->nombre = "Mengano";
$item->apellido = "Ramirez";
$item->fecha_nacimiento = "1985-09-02";
$item->update();
```

Otro modo de actualizar es enviando un arreglo con la información, siempre enviando la llave primaria del registro

```php
<?php

$datos = ["id" => 1, 
"nombre" => "Mengano", 
"apellido" => "Ramirez",
"fecha_nacimiento" => "1985-09-02"];

$item = new TItem('persona');
$item->update($datos);
```

Esto es particularmente útil cuando se reciben datos por medio de los request GET y POST, ya que por ser arreglos, se pueden enviar directamente al objeto transaccional:

```php
<?php

$item = new TItem('persona');
$item->update($_GET);
```

Al actualizar no importa si la llave primaria es autoincremental o no, no se generará ni un identificador, solamente retornará true.

### Eliminar

Para actualizar un registro se crea un objeto TItem y se envía la tabla sobre la cual se hará la operación.

```php
<?php
$item = new TItem('persona');
```

Para eliminar solamente se debe enviar las llaves primarias del registro, hay que recordar que en PHP se pueden crear atributos dinámicamente, no es necesario declararlos dentro del objeto.

```php
<?php
$item = new TItem('persona');
$item->id = 1;
```

Una vez llenados los campos, se ejecuta el método para eliminar

```php
<?php
$item = new TItem('persona');
$item->id = 1;
$item->delete();
```

Otro modo de actualizar es enviando un arreglo con la información, por ser eliminar, solo es necesario enviar la llave primaria del registro

```php
<?php

$datos = ["id" => 1];

$item = new TItem('persona');
$item->delete($datos);
```

Esto es particularmente útil cuando se reciben datos por medio de los request GET y POST, ya que por ser arreglos, se pueden enviar directamente al objeto transaccional:

```php
<?php

$item = new TItem('persona');
$item->delete($_GET);
```

Al eliminar no importa si la llave primaria es autoincremental o no, no se generará ni un identificador, solamente retornará true.

### Cargar

Para cargar un registro se crea un objeto TItem y se envía la tabla sobre la cual se hará la operación.

```php
<?php
$item = new TItem('persona');
```

Para cargar solamente se debe enviar las llaves primarias del registro, hay que recordar que en PHP se pueden crear atributos dinámicamente, no es necesario declararlos dentro del objeto.

```php
<?php
$item = new TItem('persona');
$item->id = 1;
```

Una vez llenados los campos, se ejecuta el método para cargar

```php
<?php
$item = new TItem('persona');
$item->id = 1;
$item->load();
```

Otro modo de cargar es enviando un arreglo con la información, solo es necesario enviar la llave primaria del registro

```php
<?php

$datos = ["id" => 1];

$item = new TItem('persona');
$item->load($datos);
```

Esto es particularmente útil cuando se reciben datos por medio de los request GET y POST, ya que por ser arreglos, se pueden enviar directamente al objeto transaccional:

```php
<?php

$item = new TItem('persona');
$item->load($_GET);
```

Al cargar un objeto, el método retorna un arreglo con los datos correspondientes al registro

```php
<?php

$item = new TItem('persona');
$arreglo = $item->load($_GET);
echo $arreglo['nombre'];
```

De igual forma se pueden acceder a los datos desde el mismo objeto

```php
<?php

$item = new TItem('persona');
$arreglo = $item->load($_GET);
echo $item->nombre;
```

### Guardar

Una forma que integra el insertar o actualizar registros es utilizar el metodo **save**. Este metodo verifica la información recibida y decide que operación utilizar. Si los datos no llevan la llave primaria, insertará y si la tabla es de identificador autoincrementable, retornará el valor del id


```php
<?php
$item = new TItem('persona');
$item->nombre = "Mengano";
$item->apellido = "Ramirez";
$item->fecha_nacimiento = "1985-09-02";
$var = $item->save();
echo $var; //valor del id autogenerado
```

Pero si lleva la llave primaria, realizará una actualización y retornará true

```php
<?php
$item = new TItem('persona');
$item->id = 1;
$item->nombre = "Mengano";
$item->apellido = "Ramirez";
$item->fecha_nacimiento = "1985-09-02";
$var = $item->save();
echo $var; //despliega 1, como valor de true
```

### Deshabilitar

En algun requerimiento los registros no deben ser eliminados si no desactivados, para esto se utiliza el método **disable**. Como prerequisito, la tabla debe tener un campo llamado **estado**, el cual debe ser tipo Varchar(1), no nulo y con valor predeterminado 1 (esto es que para cuando se cree el registro, siempre se encuentre habilitado o bien, dependiendo del requerimiento).

Para deshabilitar un registro se crea un objeto TItem y se envía la tabla sobre la cual se hará la operación.

```php
<?php
$item = new TItem('persona');
```

Para deshabilitar solamente se debe enviar las llaves primarias del registro.

```php
<?php
$item = new TItem('persona');
$item->id = 1;
```

Una vez llenados los campos, se ejecuta el método para deshabilitar

```php
<?php
$item = new TItem('persona');
$item->id = 1;
$item->disable();
```

El método es de doble propósito, si el registro ya estaba deshabilitado, el método lo chequeará y lo habilitará dandole como valor **1** al campo estado, de otro modo, lo deshabilitará asignando como valor **0**

### Remover

Este método combina las funcionalidades de eliminar y deshabilitar, pero lo hace solamente en registros que tienen relaciones con otro. Por ejemplo, se almacenan los teléfonos de las personas en otra tabla y se tiene una llave foranea en dicha tabla.

```sql
CREATE TABLE `telefono` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(45) NOT NULL,
  `id_persona` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_persona_idx` (`id_persona`),
  CONSTRAINT `fk_persona` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Si se desea eliminar una persona, pero tiene un registro de teléfono asociado, dará una excepción de que no se puede eliminar el registro. Pero con el método remove, al no poder eliminar, deshabilita el registro de persona. Si el registro de persona no tiene datos relacionados, entonces elimina el registro.

Para remover un registro se crea un objeto TItem y se envía la tabla sobre la cual se hará la operación.

```php
<?php
$item = new TItem('persona');
```

Para remover solamente se debe enviar las llaves primarias del registro.

```php
<?php
$item = new TItem('persona');
$item->id = 1;
```

Una vez llenados los campos, se ejecuta el método remover

```php
<?php
$item = new TItem('persona');
$item->id = 1;
$item->remove();
```

Es importante recordar que para utilizar el metodo **remove**, al igual que el metodo **disable** la tabla debe tener el campo estado para que funcione.