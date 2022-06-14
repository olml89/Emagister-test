[![Coverage Status](https://codecov.io/gh/olml89/Emagister-test/branch/main/graph/badge.svg)](https://codecov.io/gh/olml89/Emagister-test)

# Reparto de Herencias
Implementación de un test técnico para la candidatura a un rol de desarrollador sénior PHP en Emagister

## Dominio

### Miembros
Cada familiar tiene una fecha de nacimiento y un nombre único, todos los miembros mueren a
los 100 años de edad. Cada miembro con hijos siempre tendrá más edad que cualquiera de
sus hijos.

### Bienes
Cada miembro de la unidad familiar puede poseer los siguientes bienes:
- **Tierras:** Este bien no es divisible y se cuantifica en m2. Si un familiar recibe tierras y ya tenía,
  simplemente se suman sus cantidades pero se considera el mismo bien. Cada m2 equivale a
  300€.
- **Dinero:** Este bien es divisible y se cuantifica en Euros. Un euro no es fraccionable.
- **Propiedades inmobiliarias:** Estos bienes son indivisibles y se cuantifican por unidades. Cada unidad
  equivale a 1000000€.

## Reparto de la herencia
Al morir, la herencia se reparte del siguiente modo:
- El dinero se recibe a partes iguales entre todos los descendientes. Estos a su vez
  reparten el 50% de lo recibido entre sus descendientes y así sucesivamente. Las
  divisiones se realizarán redondeando aritméticamente. (Ejemplo: Un miembro A que
  tiene 3 hijos (B, C, D) recibe 100000€. A se quedará con 50000€. B, C, D recibirán
  16667€, 16667€ y 16666€ respectivamente. Si alguno de los hijos (B, C, D) tuviera
  hijos, Repartirá el 50% de lo recibido entre ellos, etc.)
- Las tierras, se entregarán al mayor de los hijos siempre. Si hay dos con misma edad se
  entregará al primero ordenado por nombres alfabéticamente.
- Las propiedades se repartirán de una en una desde el menor al mayor volviendo a
  empezar por el mayor en caso de haber más propiedades que hijos.
- Si la persona que ha muerto no tiene hijos no se realizará cálculo de herencia.

## Use case
Necesitamos saber el estado del patrimonio de cada miembro de la familia consultando una api
del siguiente modo:
```php
    /**
    * @param string $name Member name
    * @param Family $family Family structure
    * @param \DateTime $currentDate
    *
    * @return int Total member’s amount at current date
    */
    $api->getHeritageByName($name, $family, $currentDate);
```

## Se valorará
- Implementación de tests de la funcionalidad
- Uso de patrones de diseño
- Code style

## Ejemplo
Imaginemos el siguiente árbol:

![Árbol familiar](https://github.com/olml89/Emagister-test/blob/main/tree.png?raw=true)

Si A muere, el dinero se reparte a partes iguales entre los sucesores directos (B y C). Y luego, cada
sucesor directo reparte el 50% de los recibido entre sus descendientes.
En este caso sería: B recibe el 50% de A y C el otro 50%. Del 50% de A, el 50% se lo queda A y el otro 50% se reparte entre
sus descendientes. Y así recursivamente. D se quedará con el 50% de lo recibido y dará el otro 50% a
sus descendientes por partes iguales.

Si A tuviera 100.000 € el reparto a su muerte (tras 100 años de su nacimiento) sería:

**B: 25K €**

**C: 25K €**

**D: 4167 € (25K/6 €)**

**E, F: 8333 € cada uno (25K/3 €)**

**I, J: 2084 € y 2083 € (25K/12 €)**

**G, H: 12500 € cada uno (25K/2 €)**

## Discusión de la implementación
La aplicación se usa del siguiente modo:
```php
    use Heritages\App\Domain\Entities\Members\Member;
    use Heritages\App\Domain\Entities\Assets\Money;
    use Heritages\App\Domain\Services\HeritageCalculator\HeritageCalculator;
    
    // Creación de una familia de raíz siguiendo el ejemplo 1 de la práctica
    $A = Member::born('A', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));
    $B = $A->giveBirth('B', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950'));
    $D = $B->giveBirth('D', DateTimeImmutable::createFromFormat('d/m/Y', '08/08/1980'));
    $I = $D->giveBirth('I', DateTimeImmutable::createFromFormat('d/m/Y', '10/10/2010'));
    $J = $D->giveBirth('J', DateTimeImmutable::createFromFormat('d/m/Y', '03/03/2012'));
    $E = $B->giveBirth('E', DateTimeImmutable::createFromFormat('d/m/Y', '06/07/1982'));
    $F = $B->giveBirth('F', DateTimeImmutable::createFromFormat('d/m/Y', '07/02/1984'));
    $C = $A->giveBirth('C', DateTimeImmutable::createFromFormat('d/m/Y', '01/02/1953'));
    $G = $C->giveBirth('G', DateTimeImmutable::createFromFormat('d/m/Y', '11/03/1985'));
    $H = $C->giveBirth('H', DateTimeImmutable::createFromFormat('d/m/Y', '04/09/1986'));
    
    // A recibe 100000 €
    $A->addAsset(new Money(100000));
    
    // Creamos un servicio de HeritageCalculator
    $heritageCalculator = new HeritageCalculator();
    
    // Podemos calcular la herencia de cualquier miembro
    echo $I->getHeritage($heritageCalculator); // 2084
    
    // Esta es la sintaxis alternativa, llamando desde el exterior del objeto miembro
    echo $heritageCalculator->getHeritage($I); // 2084
    
    // También podemos calcular el patrimonio total de cualquier miembro
    echo $I->getPatrimony($heritageCalculator); // 2084
    
    // En el caso anterior es lo mismo que la herencia, porque I no tiene ningún patrimonio propio. Pero si lo creamos:
    $I->addAsset(new Money(1000));
    echo $I->getPatrimony($heritageCalculator); // 3084
    
    // Calcular sin usar una referencia temporal significa usar la del momento presente, y en el año 2022
    // el único miembro de esa familia que se considera "muerto" es A, porque han pasado más de 100 años de
    // su nacimiento. Si usamos una fecha en la que B está muerto, por ejemplo, podemos ver que sus
    // herederos heredan sus bienes
    $certainDeadForB = DateTimeImmutable::createFromFormat('d/m/Y', '06/05/2050');
    echo $B->getPatrimony($heritageCalculator, $certainDeadForB); // 0, porque está muerto
    echo $I->getHeritage($heritageCalculator, $certainDeadForB); // 4167 = 2084 + 2083 heredados de B
    echo $I->getPatrimony($heritageCalculator, $certainDeadForB); // 5167 = 3084 + 2083 heredados de B
    
    // Una demostración más extensa, usando tests y otros objetos de Asset, se puede encontrar aquí:
    // https://github.com/olml89/Emagister-test/blob/main/tests/Unit/HeritageCalculatorTest.php    
```
Desde el punto de vista técnico, para la estructura de familia se ha usado un árbol en el que cada miembro contiene
una posible referencia a su padre y una colección de hijos, cada uno de ellos apuntando al padre, y así sucesivamente.
De esta manera hemos podido implementar los algoritmos de búsqueda de elementos y cálculo de valores de ancestros
usando la recursión de una forma sencilla.

Para resolver la práctica se usa en parte el patrón Visitor.

Los algoritmos implementados NO modifican el estado interno de los objetos que forman el árbol familiar. Esto se ha
decidido como detalle de implementación puesto que me ha parecido que el enunciado sugería un uso parecido al siguiente:
```php
    $heritageInDate1 = $heritageCalculator->getHeritage($member, $date1); 
    
    // ...
    
    $heritageInDate2 = $heritageCalculator->getHeritage($member, $date2);  
```
De tal manera que se pueden crear distintos "snapshots" del estado de la estructura en una fecha u otra. Además
de que no me parecía técnicamente muy correcto realizar modificaciones en el estado interno de los objetos
consultados dentro de un getter, si los datos encapsulados por la estructura se modificaran durante las llamadas, sería
imposible realizar consultas sucesivas sobre estado hipotético del sistema, ya que el estado inicial del mismo cambiaría.