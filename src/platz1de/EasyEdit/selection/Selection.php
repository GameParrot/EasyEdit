<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\math\Vector3;

abstract class Selection
{
	use ReferencedWorldHolder;

	protected Vector3 $pos1;
	protected Vector3 $pos2;
	protected Vector3 $selected1;
	protected Vector3 $selected2;

	/**
	 * Selection constructor.
	 * @param string       $world
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 */
	public function __construct(string $world, ?Vector3 $pos1, ?Vector3 $pos2)
	{
		$this->world = $world;

		if ($pos1 !== null) {
			$this->pos1 = clone($this->selected1 = $pos1->floor());
		}
		if ($pos2 !== null) {
			$this->pos2 = clone($this->selected2 = $pos2->floor());
		}

		$this->update();
	}

	/**
	 * @return int[]
	 */
	abstract public function getNeededChunks(): array;

	public function getPos1(): Vector3
	{
		return $this->pos1;
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @return bool whether the chunk should be cached to aid in later executions
	 */
	abstract public function shouldBeCached(int $x, int $z): bool;

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return $this->getPos1();
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return $this->getPos2();
	}

	/**
	 * @return Vector3
	 */
	public function getSize(): Vector3
	{
		return $this->getPos2()->subtractVector($this->getPos1())->add(1, 1, 1);
	}

	/**
	 * @return Vector3
	 */
	public function getBottomCenter(): Vector3
	{
		return $this->getPos1()->addVector($this->getPos2())->divide(2)->withComponents(null, $this->getPos1()->getY(), null);
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	abstract public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator;

	/**
	 * @return bool
	 */
	public function isValid(): bool
	{
		return isset($this->pos1, $this->pos2);
	}

	/**
	 * calculating the "real" positions (selected ones don't have to be the smallest and biggest
	 * they could be mixed)
	 */
	protected function update(): void
	{
		if ($this->isValid()) {
			$pos = $this->pos1;
			$this->pos1 = VectorUtils::enforceHeight(Vector3::minComponents($this->pos1, $this->pos2));
			$this->pos2 = VectorUtils::enforceHeight(Vector3::maxComponents($pos, $this->pos2));
		}
	}

	/**
	 * @param Vector3 $pos1
	 */
	public function setPos1(Vector3 $pos1): void
	{
		$this->pos1 = clone($this->selected1 = $pos1);
		if (isset($this->selected2)) {
			$this->pos2 = clone($this->selected2);
		}

		$this->update();
	}

	/**
	 * @param Vector3 $pos2
	 */
	public function setPos2(Vector3 $pos2): void
	{
		if (isset($this->selected1)) {
			$this->pos1 = clone($this->selected1);
		}
		$this->pos2 = clone($this->selected2 = $pos2);

		$this->update();
	}

	/**
	 * @return Vector3
	 */
	public function getPos2(): Vector3
	{
		return $this->pos2;
	}

	/**
	 * @param int $block
	 * @return bool
	 */
	public static function processBlock(int &$block): bool
	{
		$return = ($block !== 0);

		if ($block === 0xD90) { //structure_void
			$block = 0;
		}

		return $return;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putVector($this->pos1);
		$stream->putVector($this->pos2);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->pos1 = $stream->getVector();
		$this->pos2 = $stream->getVector();
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putString(igbinary_serialize($this) ?? "");
		$this->putData($stream);
		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return Selection
	 */
	public static function fastDeserialize(string $data): Selection
	{
		$stream = new ExtendedBinaryStream($data);
		/** @var Selection $selection */
		$selection = igbinary_unserialize($stream->getString());
		$selection->parseData($stream);
		return $selection;
	}

	/**
	 * @return array{string}
	 */
	public function __serialize(): array
	{
		return [$this->world];
	}

	/**
	 * @param array{string} $data
	 * @return void
	 */
	public function __unserialize(array $data): void
	{
		$this->world = $data[0];
	}
}