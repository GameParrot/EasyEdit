<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use Generator;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class CylindricalConstructor extends ShapeConstructor
{
	protected Vector3 $position;
	protected float $radius;
	protected int $height;

	/**
	 * @param Closure $closure
	 * @param Vector3 $position
	 * @param float   $radius
	 * @param int     $height
	 */
	public function __construct(Closure $closure, Vector3 $position, float $radius, int $height)
	{
		parent::__construct($closure);
		$this->position = $position;
		$this->radius = $radius;
		$this->height = $height;
	}

	public function getBlockCount(): int
	{
		return (int) (M_PI * ($this->radius ** 2) * $this->height);
	}

	public function moveTo(int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		$radiusSquared = $this->radius ** 2;
		$radius = ceil($this->radius);
		$minX = max($min->getX() - $this->position->getX(), -$radius);
		$maxX = min($max->getX() - $this->position->getX(), $radius);
		$minZ = max($min->getZ() - $this->position->getZ(), -$radius);
		$maxZ = min($max->getZ() - $this->position->getZ(), $radius);
		$closure = $this->closure;
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = 0; $y < $this->height; $y++) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($this->position->getX() + $x, $this->position->getY() + $y, $this->position->getZ() + $z);
					}
				}
			}
		}
	}

	/**
	 * @param Vector3 $position
	 * @param float   $radius
	 * @param float   $thickness
	 * @param int     $height
	 * @param Closure $closure
	 * @return Generator<CylindricalConstructor>
	 */
	public static function hollowAround(Vector3 $position, float $radius, float $thickness, int $height, Closure $closure): Generator
	{
		yield new TubeConstructor($closure, $position->up(), $radius, $height - 2, $thickness);
		yield new self($closure, $position, $radius, (int) $thickness);
		yield new self($closure, $position->up($height - 1), $radius, (int) $thickness);
	}

	public function offset(Vector3 $offset): ShapeConstructor
	{
		return new self($this->closure, $this->position->addVector($offset), $this->radius, $this->height);
	}
}