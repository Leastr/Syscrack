<?php
namespace Framework\Syscrack\Game\Processes;

/**
 * Lewis Lancaster 2017
 *
 * Class Hack
 *
 * @package Framework\Syscrack\Game\Processes
 */

use Framework\Application\Container;
use Framework\Exceptions\SyscrackException;
use Framework\Syscrack\Game\Softwares;
use Framework\Syscrack\Game\Structures\Process;
use Framework\Syscrack\Game\Internet;
use Framework\Syscrack\Game\Computer;
use Framework\Syscrack\Game\AddressDatabase;
use Flight;
use Framework\Syscrack\Game\Utilities\TimeHelper;

class Hack implements Process
{

    /**
     * @var Internet
     */

    protected $internet;

    protected $computer;

    protected $addressdatabase;

    protected $softwares;

    /**
     * Logout constructor.
     */

    public function __construct()
    {

        $this->internet = new Internet();

        $this->computer = new Computer();

        $this->softwares = new Softwares();
    }

    /**
     * Called when this process request is created
     *
     * @param $timecompleted
     *
     * @param $computerid
     *
     * @param $userid
     *
     * @param $process
     *
     * @param array $data
     *
     * @return mixed
     */

    public function onCreation($timecompleted, $computerid, $userid, $process, array $data)
    {

        if( isset( $data['ipaddress'] ) == false )
        {

            return false;
        }

        $this->addressdatabase = new AddressDatabase( Container::getObject('session')->getSessionUser() );

        $usercomputer = $this->computer->getComputer( $this->computer->getCurrentUserComputer() );

        $computer = $this->internet->getComputer( $data['ipaddress'] );

        if( $this->softwares->getDatabaseSoftware( $this->computer->getCracker( $usercomputer->computerid ) )->level
            < $this->softwares->getDatabaseSoftware( $this->computer->getHasher( $computer->computerid ) )->level )
        {

            return false;
        }

        if( $this->addressdatabase->getComputerByIPAddress( $data['ipaddress' ] ) != null )
        {

            return false;
        }

        return true;
    }

    /**
     * Called when this process request is created
     *
     * @param $timecompleted
     *
     * @param $computerid
     *
     * @param $userid
     *
     * @param $process
     *
     * @param array $data
     */

    public function onCompletion($timecompleted, $timestarted, $computerid, $userid, $process, array $data)
    {

        if( isset( $data['ipaddress'] ) == false )
        {

            throw new SyscrackException();
        }

        $this->addressdatabase = new AddressDatabase( Container::getObject('session')->getSessionUser() );

        $this->addressdatabase->addComputer( array(
            'computerid'        => $this->internet->getComputer( $data['ipaddress'] )->computerid,
            'ipaddress'         => $data['ipaddress'],
            'timehacked'        => time()
        ));

        $this->addressdatabase->saveDatabase();

        Flight::redirect('/game/internet/' . $data['ipaddress'] . '/login');
    }

    /**
     * Gets the completion time
     *
     * @param $computerid
     *
     * @param $ipaddress
     *
     * @param $process
     *
     * @return null
     */

    public function getCompletionTime($computerid, $ipaddress, $process)
    {

        $future = new TimeHelper();

        return $future->getSecondsInFuture(10);
    }

    /**
     * Redirects the user to an error page
     *
     * @param string $message
     *
     * @param string $ipaddress
     */

    private function redirectError( $message='', $ipaddress='' )
    {

        if( $ipaddress !== '' )
        {

            Flight::redirect('/game/internet/' . $ipaddress . "?error=" . $message ); exit;
        }

        Flight::redirect('/game/internet/?error=' . $message ); exit;
    }

    /**
     * Redirects the user to a success page
     *
     * @param string $ipaddress
     */

    private function redirectSuccess( $ipaddress='' )
    {

        if( $ipaddress !== '' )
        {

            Flight::redirect('/game/internet/' . $ipaddress . "?success" ); exit;
        }

        Flight::redirect('/game/internet/?success'); exit;
    }
}