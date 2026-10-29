<?php
class Coupon {
	private $pdo;
	private $code;
	private $discount_type;
	private $discount_value;
	private $min_order_amount;
	private $usage_limit;
	private $starts_at;
	private $expires_at;
	private $is_active;
	private $created_at;

	public function __construct($db_connection,
		$code = '',
		$discount_type = 'percentage',
		$discount_value = 0,
		$min_order_amount = 0,
		$usage_limit = null,
		$starts_at = null,
		$expires_at = null,
		$is_active = 0,
		$created_at = null) {
		$this->pdo = $db_connection;
		$this->code = $code;
		$this->discount_type = $discount_type;
		$this->discount_value = $discount_value;
		$this->min_order_amount = $min_order_amount;
		$this->usage_limit = $usage_limit;
		$this->starts_at = $starts_at;
		$this->expires_at = $expires_at;
		$this->is_active = $is_active;
		$this->created_at = $created_at;
	}

	public function create() {
		$sql = 'INSERT INTO coupons(code, discount_type, discount_value, min_order_amount, usage_limit, starts_at, expires_at, is_active)
			VALUES(:code, :discount_type, :discount_value, :min_order_amount, :usage_limit, :starts_at, :expires_at, :is_active)';
		// $stmt = $this->pdo->prepare($sql);
		try {
			return $this->pdo->run_query($sql, [
				'code' => $this->code,
				'discount_type' => $this->discount_type,
				'discount_value' => $this->discount_value,
				'usage_limit' => $this->usage_limit,
				'starts_at' => $this->starts_at,
				'expires_at' => $this->expires_at,
				'min_order_amount' => $this->min_order_amount,
				'is_active' => $this->is_active
			]);
		} catch(PDOException $e) {
			// die("MySQL Error: " . $e->getMessage());
			throw $e;
		}
	}
}
?>
