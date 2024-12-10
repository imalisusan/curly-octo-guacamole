<?php

class BigInt {
    private $value;
    private $negative;

    public function __construct(string $value) {
        // Remove leading zeros and determine if the number is negative
        $this->value = ltrim($value, '0');
        $this->negative = false;

        if ($this->value === '') {
            $this->value = '0';
        }

        if ($this->value[0] === '-') {
            $this->negative = true;
            $this->value = substr($this->value, 1);
        }
    }

    public function __toString() {
        return ($this->negative && $this->value !== '0' ? '-' : '') . $this->value;
    }

    private function compareAbs(BigInt $other): int {
        // Compare absolute values
        if (strlen($this->value) !== strlen($other->value)) {
            return strlen($this->value) - strlen($other->value);
        }
        return strcmp($this->value, $other->value);
    }

    public function add(BigInt $other): BigInt {
        if ($this->negative === $other->negative) {
            // Same sign: Add absolute values
            $result = $this->addAbs($this->value, $other->value);
            return new BigInt(($this->negative ? '-' : '') . $result);
        } else {
            // Different signs: Subtract smaller from larger
            if ($this->compareAbs($other) >= 0) {
                $result = $this->subtractAbs($this->value, $other->value);
                return new BigInt(($this->negative ? '-' : '') . $result);
            } else {
                $result = $this->subtractAbs($other->value, $this->value);
                return new BigInt(($other->negative ? '-' : '') . $result);
            }
        }
    }

    public function subtract(BigInt $other): BigInt {
        // Subtraction is addition of the negative
        $other->negative = !$other->negative;
        return $this->add($other);
    }

    public function multiply(BigInt $other): BigInt {
        $result = $this->multiplyAbs($this->value, $other->value);
        $negative = $this->negative !== $other->negative;
        return new BigInt(($negative ? '-' : '') . $result);
    }

    public function divide(BigInt $other): BigInt {
        if ($other->value === '0') {
            throw new Exception("Division by zero");
        }
        $quotient = $this->divideAbs($this->value, $other->value);
        $negative = $this->negative !== $other->negative;
        return new BigInt(($negative ? '-' : '') . $quotient);
    }

    public function mod(BigInt $other): BigInt {
        if ($other->value === '0') {
            throw new Exception("Modulo by zero");
        }
        $remainder = $this->modAbs($this->value, $other->value);
        return new BigInt(($this->negative ? '-' : '') . $remainder);
    }

    public function power(BigInt $exp): BigInt {
        if ($exp->negative) {
            throw new Exception("Negative exponent not supported");
        }
        $base = $this->value;
        $result = '1';

        while ($exp->value !== '0') {
            if ((int)$exp->value[strlen($exp->value) - 1] % 2 === 1) {
                $result = $this->multiplyAbs($result, $base);
            }
            $base = $this->multiplyAbs($base, $base);
            $exp = $exp->divide(new BigInt('2'));
        }

        return new BigInt($result);
    }

    public function factorial(): BigInt {
        if ($this->negative) {
            throw new Exception("Factorial of a negative number is not defined");
        }
        $result = '1';
        $num = $this->value;

        while ($num !== '0') {
            $result = $this->multiplyAbs($result, $num);
            $num = $this->subtractAbs($num, '1');
        }

        return new BigInt($result);
    }

    private function addAbs(string $a, string $b): string {
        $carry = 0;
        $result = '';
        $a = str_pad($a, max(strlen($a), strlen($b)), '0', STR_PAD_LEFT);
        $b = str_pad($b, max(strlen($a), strlen($b)), '0', STR_PAD_LEFT);

        for ($i = strlen($a) - 1; $i >= 0; $i--) {
            $sum = (int)$a[$i] + (int)$b[$i] + $carry;
            $carry = intdiv($sum, 10);
            $result = ($sum % 10) . $result;
        }
        if ($carry) {
            $result = $carry . $result;
        }

        return $result;
    }

    private function subtractAbs(string $a, string $b): string {
        $borrow = 0;
        $result = '';
        $a = str_pad($a, max(strlen($a), strlen($b)), '0', STR_PAD_LEFT);
        $b = str_pad($b, max(strlen($a), strlen($b)), '0', STR_PAD_LEFT);

        for ($i = strlen($a) - 1; $i >= 0; $i--) {
            $diff = (int)$a[$i] - (int)$b[$i] - $borrow;
            if ($diff < 0) {
                $diff += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }
            $result = $diff . $result;
        }

        return ltrim($result, '0') ?: '0';
    }

    private function multiplyAbs(string $a, string $b): string {
        $result = array_fill(0, strlen($a) + strlen($b), 0);

        for ($i = strlen($a) - 1; $i >= 0; $i--) {
            for ($j = strlen($b) - 1; $j >= 0; $j--) {
                $product = (int)$a[$i] * (int)$b[$j] + $result[$i + $j + 1];
                $result[$i + $j + 1] = $product % 10;
                $result[$i + $j] += intdiv($product, 10);
            }
        }

        return ltrim(implode('', $result), '0') ?: '0';
    }

    private function divideAbs(string $a, string $b): string {
        $result = '';
        $remainder = '';

        for ($i = 0; $i < strlen($a); $i++) {
            $remainder .= $a[$i];
            $quotient = 0;

            while ($this->compareAbs(new BigInt($remainder), new BigInt($b)) >= 0) {
                $remainder = $this->subtractAbs($remainder, $b);
                $quotient++;
            }

            $result .= $quotient;
        }

        return ltrim($result, '0') ?: '0';
    }

    private function modAbs(string $a, string $b): string {
        $remainder = '';

        for ($i = 0; $i < strlen($a); $i++) {
            $remainder .= $a[$i];

            while ($this->compareAbs(new BigInt($remainder), new BigInt($b)) >= 0) {
                $remainder = $this->subtractAbs($remainder, $b);
            }
        }

        return $remainder ?: '0';
    }
}

// Simple REPL for testing
function repl() {
    echo "BigInt Calculator (type 'exit' to quit)\n";

    while (true) {
        echo ">> ";
        $input = trim(fgets(STDIN));

        if ($input === 'exit') {
            break;
        }

        try {
            eval('$result = ' . $input . ';');
            echo $result . "\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

repl();
