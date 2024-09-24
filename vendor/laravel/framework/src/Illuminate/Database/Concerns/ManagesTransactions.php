<?php
/**
 * 数据库，管理事务特征
 */

namespace Illuminate\Database\Concerns;

use Closure;
use Exception;
use Throwable;

trait ManagesTransactions
{
    /**
     * Execute a Closure within a transaction.
	 * 在事务中执行闭包
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $this->beginTransaction();

            // We'll simply execute the given callback within a try / catch block and if we
            // catch any exception we can rollback this transaction so that none of this
            // gets actually persisted to a database or stored in a permanent fashion.
			// 我们只需在try/catch块中执行给定的回调，
			// 如果我们捕获任何异常，我们可以回滚此事务，
			// 实际持久化到数据库或以永久方式存储
            try {
                $callbackResult = $callback($this);
            }

            // If we catch an exception we'll rollback this transaction and try again if we
            // are not out of attempts. If we are out of attempts we will just throw the
            // exception back out and let the developer handle an uncaught exceptions.
			// 如果我们捕获到异常，我们将回滚此事务并再次尝试。
			// 如果我们没有尝试，我们会抛出异常，并让开发人员处理未捕获的异常。
            catch (Exception $e) {
                $this->handleTransactionException(
                    $e, $currentAttempt, $attempts
                );

                continue;
            } catch (Throwable $e) {
                $this->rollBack();

                throw $e;
            }

            try {
                if ($this->transactions == 1) {
                    $this->getPdo()->commit();
                }

                $this->transactions = max(0, $this->transactions - 1);
            } catch (Exception $e) {
                $commitFailed = true;

                $this->handleCommitTransactionException(
                    $e, $currentAttempt, $attempts
                );

                continue;
            }

            if (! isset($commitFailed)) {
                $this->fireConnectionEvent('committed');
            }

            return $callbackResult;
        }
    }

    /**
     * Handle an exception encountered when running a transacted statement.
	 * 处理运行事务语句时遇到的异常
     *
     * @param  \Exception  $e
     * @param  int  $currentAttempt
     * @param  int  $maxAttempts
     * @return void
     *
     * @throws \Exception
     */
    protected function handleTransactionException($e, $currentAttempt, $maxAttempts)
    {
        // On a deadlock, MySQL rolls back the entire transaction so we can't just
        // retry the query. We have to throw this exception all the way out and
        // let the developer handle it in another way. We will decrement too.
		// 在死锁时，MySQL会回滚整个事务，因此我们不能只是重试查询。
		// 我们必须彻底抛出这个异常，让开发人员以另一种方式处理它。
        if ($this->causedByConcurrencyError($e) &&
            $this->transactions > 1) {
            $this->transactions--;

            throw $e;
        }

        // If there was an exception we will rollback this transaction and then we
        // can check if we have exceeded the maximum attempt count for this and
        // if we haven't we will return and try this query again in our loop.
		// 如果发生异常，我们将回滚此事务，然后可以检查我们是否已超过此操作的最大浓度次数，
		// 如果没有，我们将返回并在循环中再次尝试此查询。
        $this->rollBack();

        if ($this->causedByConcurrencyError($e) &&
            $currentAttempt < $maxAttempts) {
            return;
        }

        throw $e;
    }

    /**
     * Start a new database transaction.
	 * 启动一个新的数据库事务
     *
     * @return void
     *
     * @throws \Exception
     */
    public function beginTransaction()
    {
        $this->createTransaction();

        $this->transactions++;

        $this->fireConnectionEvent('beganTransaction');
    }

    /**
     * Create a transaction within the database.
	 * 在数据库中创建一个事务
     *
     * @return void
     */
    protected function createTransaction()
    {
        if ($this->transactions == 0) {
            $this->reconnectIfMissingConnection();

            try {
                $this->getPdo()->beginTransaction();
            } catch (Exception $e) {
                $this->handleBeginTransactionException($e);
            }
        } elseif ($this->transactions >= 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->createSavepoint();
        }
    }

    /**
     * Create a save point within the database.
	 * 在数据库中创建一个保存点
     *
     * @return void
     */
    protected function createSavepoint()
    {
        $this->getPdo()->exec(
            $this->queryGrammar->compileSavepoint('trans'.($this->transactions + 1))
        );
    }

    /**
     * Handle an exception from a transaction beginning.
	 * 从事务开始处理异常
     *
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleBeginTransactionException($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->reconnect();

            $this->getPdo()->beginTransaction();
        } else {
            throw $e;
        }
    }

    /**
     * Commit the active database transaction.
	 * 提交活动数据库事务
     *
     * @return void
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->getPdo()->commit();
        }

        $this->transactions = max(0, $this->transactions - 1);

        $this->fireConnectionEvent('committed');
    }

    /**
     * Handle an exception encountered when committing a transaction.
	 * 处理提交事务时遇到的异常
     *
     * @param  \Exception  $e
     * @param  int  $currentAttempt
     * @param  int  $maxAttempts
     * @return void
     */
    protected function handleCommitTransactionException($e, $currentAttempt, $maxAttempts)
    {
        $this->transactions = max(0, $this->transactions - 1);

        if ($this->causedByConcurrencyError($e) &&
            $currentAttempt < $maxAttempts) {
            return;
        }

        if ($this->causedByLostConnection($e)) {
            $this->transactions = 0;
        }

        throw $e;
    }

    /**
     * Rollback the active database transaction.
	 * 回滚活动数据库事务
     *
     * @param  int|null  $toLevel
     * @return void
     *
     * @throws \Exception
     */
    public function rollBack($toLevel = null)
    {
        // We allow developers to rollback to a certain transaction level. We will verify
        // that this given transaction level is valid before attempting to rollback to
        // that level. If it's not we will just return out and not attempt anything.
		// 我们允许开发人员回滚到某个事件级别。
		// 在尝试回滚到该级别之前，我们将验证此给定的事务级别是否有效。
		// 如果不是这样，我们只会回来，不尝试任何事情。
        $toLevel = is_null($toLevel)
                    ? $this->transactions - 1
                    : $toLevel;

        if ($toLevel < 0 || $toLevel >= $this->transactions) {
            return;
        }

        // Next, we will actually perform this rollback within this database and fire the
        // rollback event. We will also set the current transaction level to the given
        // level that was passed into this method so it will be right from here out.
		// 接下来，我们将在此数据库中实际执行回滚操作，并触发回滚事件。
		// 我们还将把当前交易级别设置为给定的传递到此方法中的级别，因此它将从这里开始。
        try {
            $this->performRollBack($toLevel);
        } catch (Exception $e) {
            $this->handleRollBackException($e);
        }

        $this->transactions = $toLevel;

        $this->fireConnectionEvent('rollingBack');
    }

    /**
     * Perform a rollback within the database.
	 * 执行回滚在数据库中
     *
     * @param  int  $toLevel
     * @return void
     */
    protected function performRollBack($toLevel)
    {
        if ($toLevel == 0) {
            $this->getPdo()->rollBack();
        } elseif ($this->queryGrammar->supportsSavepoints()) {
            $this->getPdo()->exec(
                $this->queryGrammar->compileSavepointRollBack('trans'.($toLevel + 1))
            );
        }
    }

    /**
     * Handle an exception from a rollback.
	 * 处理异常从回滚
     *
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleRollBackException($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->transactions = 0;
        }

        throw $e;
    }

    /**
     * Get the number of active transactions.
	 * 得到活动事务的数量
     *
     * @return int
     */
    public function transactionLevel()
    {
        return $this->transactions;
    }
}
