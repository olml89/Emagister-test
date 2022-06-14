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