<?php
class Coupon {
	private $pdo;

	public function __construct($db_connection) {
		$this->pdo = $db_connection;
	}

	public function create(array $data) {
		$sql = 'INSERT INTO coupons(code, discount_type, discount_value, min_order_amount, usage_limit, starts_at, expires_at, is_active)
			VALUES(:code, :discount_type, :discount_value, :min_order_amount, :usage_limit, :starts_at, :expires_at, :is_active)';
		try {
			return $this->pdo->run_query($sql, [
				'code' => $data['code'],
				'discount_type' => $data['discount_type'],
				'discount_value' => $data['discount_value'],
				'usage_limit' => $data['usage_limit'],
				'starts_at' => $data['starts_at'],
				'expires_at' => $data['expires_at'],
				'min_order_amount' => $data['min_order_amount'],
				'is_active' => $data['is_active']
			]);
		} catch(PDOException $e) {
			throw $e;
		}
	}

	public function update(int $id, array $data) {
		$sql = 'UPDATE coupons
				SET code = :code,
					discount_type = :discount_type,
					discount_value = :discount_value,
					min_order_amount = :min_order_amount,
					usage_limit = :usage_limit,
					starts_at = :starts_at,
					expires_at = :expires_at,
					is_active = :is_active
				WHERE id = :id';
		try {
			return $this->pdo->run_query($sql, [
				'id' => $id,
				'code' => $data['code'],
				'discount_type' => $data['discount_type'],
				'discount_value' => $data['discount_value'],
				'usage_limit' => $data['usage_limit'],
				'starts_at' => $data['starts_at'],
				'expires_at' => $data['expires_at'],
				'min_order_amount' => $data['min_order_amount'],
				'is_active' => $data['is_active']
			]);
		} catch(PDOException $e) {
			throw $e;
		}
	}

	public function delete(int $id) {
		$sql = 'DELETE FROM coupons WHERE id = :id';
		try {
			return $this->pdo->run_query($sql, ['id' => $id]);
		} catch(PDOException $e) {
			throw $e;
		}
	}
}
?>
