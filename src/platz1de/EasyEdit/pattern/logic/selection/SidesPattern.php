<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\world\ChunkController;

class SidesPattern extends Pattern
{
	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, ChunkController $iterator, Selection $current, Selection $total): bool
	{
		$thickness = $this->args->getFloat("thickness");
		if ($current instanceof Cube) {
			$min = $total->getPos1();
			$max = $total->getPos2();

			return ($x - $min->getX() + 1) <= $thickness || ($max->getX() - $x + 1) <= $thickness || ($y - $min->getY() + 1) <= $thickness || ($max->getY() - $y + 1) <= $thickness || ($z - $min->getZ() + 1) <= $thickness || ($max->getZ() - $z + 1) <= $thickness;
		}
		if ($current instanceof Cylinder) {
			return (($x - $current->getPoint()->getFloorX()) ** 2) + (($z - $current->getPoint()->getFloorZ()) ** 2) > (($current->getRadius() - $thickness) ** 2) || ($y - $current->getPos1()->getY() + 1) <= $thickness || ($current->getPos2()->getY() - $y + 1) <= $thickness;
		}
		if ($current instanceof Sphere) {
			return (($x - $current->getPoint()->getFloorX()) ** 2) + (($y - $current->getPoint()->getFloorY()) ** 2) + (($z - $current->getPoint()->getFloorZ()) ** 2) > (($current->getRadius() - $thickness) ** 2) || ($y - $current->getPos1()->getY() + 1) <= $thickness || ($current->getPos2()->getY() - $y + 1) <= $thickness;
		}
		throw new ParseError("Sides pattern does not support selection of type " . $current::class);
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeWalls($this->args->getFloat("thickness"))->includeVerticals(0);
	}

	public function check(): void
	{
		if ($this->args->getFloat("thickness") === -1.0) {
			$this->args->setFloat("thickness", 1.0);
		}
	}
}