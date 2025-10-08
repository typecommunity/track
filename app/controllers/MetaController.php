<?php
/**
 * UTMTrack - Controller do Meta Ads
 */

class MetaController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    public function index() {
        $this->render('meta/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pageTitle' => 'Meta Ads'
        ]);
    }
    
    public function accounts() {
        $userId = $this->auth->id();
        
        $accounts = $this->db->fetchAll("
            SELECT * FROM ad_accounts 
            WHERE user_id = :user_id 
            AND platform = 'meta'
            ORDER BY created_at DESC
        ", ['user_id' => $userId]);
        
        $this->render('meta/accounts', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'accounts' => $accounts,
            'pageTitle' => 'Contas Meta'
        ]);
    }
}