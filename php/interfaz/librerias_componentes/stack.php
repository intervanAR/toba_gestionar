<?php

class Stack
{
    /**
     * @var Stack El objeto que contiene la Pila
     */
    private $stack;

    /**
     * Constructor. Crea la pila.
     */
    public function __construct()
    {
        $this->stack = [];
    }

    /**
     * Inserta un elemento en el tope de la pila.
     * El formato $array[]=$v es más eficiente para un elemento que la función nativa array_push().
     *
     * @param mixed $v Elemento a insertar
     */
    public function push($v)
    {
        $this->stack[] = $v;
    }

    /**
     * Remueve el elemento al tope de la pila y lo regresa.
     *
     * @return mixed El elemento del tope.
     * Si la pila está vacía devolverá NULL
     */
    public function pop()
    {
        return array_pop($this->stack);
    }

    /**
     * Checa si la pila está vacía.
     *
     * @return bool True si está vacía, false caso contrario
     */
    public function isEmpty()
    {
        return empty($this->stack);
    }

    /**
     * Cuenta el tamaño de la pila.
     *
     * @return int El tamaño de la pila
     */
    public function length()
    {
        return count($this->stack);
    }

    /**
     * Observa el ultimo elemento de la pila, sin removerlo.
     *
     * @return mixed El último elemento de la pila
     */
    public function peek()
    {
        return $this->stack[($this->length() - 1)];
    }
}
