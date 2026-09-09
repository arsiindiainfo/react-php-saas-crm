<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    public array $authLogin = [
        'email'    => 'required|valid_email',
        'password' => 'required',
    ];

    public array $authRefresh = [
        'refreshToken' => 'required|string',
    ];

    public array $authChangePassword = [
        'currentPassword' => 'required',
        'newPassword'     => 'required|min_length[8]',
    ];

    public array $userInvite = [
        'name'      => 'required|min_length[2]|max_length[120]',
        'email'     => 'required|valid_email|max_length[190]',
        'role'      => 'required|in_list[ADMIN,SALES_MANAGER,SALES_REP]',
        'managerId' => 'permit_empty|integer',
    ];

    public array $userUpdate = [
        'role'      => 'permit_empty|in_list[ADMIN,SALES_MANAGER,SALES_REP]',
        'status'    => 'permit_empty|in_list[ACTIVE,DISABLED]',
        'managerId' => 'permit_empty|integer',
    ];

    public array $companyCreate = [
        'name'     => 'required|min_length[2]|max_length[180]',
        'industry' => 'permit_empty|max_length[100]',
        'website'  => 'permit_empty|valid_url_strict|max_length[200]',
        'phone'    => 'permit_empty|max_length[30]',
    ];

    public array $companyUpdate = [
        'name'     => 'permit_empty|min_length[2]|max_length[180]',
        'industry' => 'permit_empty|max_length[100]',
        'website'  => 'permit_empty|valid_url_strict|max_length[200]',
        'phone'    => 'permit_empty|max_length[30]',
        'status'   => 'permit_empty|in_list[PROSPECT,CUSTOMER,CHURNED]',
    ];

    public array $contactCreate = [
        'companyId' => 'required|integer',
        'firstName' => 'required|max_length[80]',
        'lastName'  => 'required|max_length[80]',
        'email'     => 'permit_empty|valid_email|max_length[190]',
        'phone'     => 'permit_empty|max_length[30]',
        'jobTitle'  => 'permit_empty|max_length[100]',
    ];

    public array $contactUpdate = [
        'firstName' => 'permit_empty|max_length[80]',
        'lastName'  => 'permit_empty|max_length[80]',
        'email'     => 'permit_empty|valid_email|max_length[190]',
        'phone'     => 'permit_empty|max_length[30]',
        'jobTitle'  => 'permit_empty|max_length[100]',
    ];

    public array $leadCreate = [
        'firstName'   => 'required|max_length[80]',
        'lastName'    => 'required|max_length[80]',
        'email'       => 'permit_empty|valid_email|max_length[190]',
        'phone'       => 'permit_empty|max_length[30]',
        'companyName' => 'permit_empty|max_length[180]',
        'source'      => 'required|in_list[WEBSITE,REFERRAL,COLD_CALL,EVENT,OTHER]',
    ];

    public array $leadUpdate = [
        'firstName'   => 'permit_empty|max_length[80]',
        'lastName'    => 'permit_empty|max_length[80]',
        'email'       => 'permit_empty|valid_email|max_length[190]',
        'phone'       => 'permit_empty|max_length[30]',
        'companyName' => 'permit_empty|max_length[180]',
        'source'      => 'permit_empty|in_list[WEBSITE,REFERRAL,COLD_CALL,EVENT,OTHER]',
        // Board drag between the three pre-conversion columns (§22.5); the
        // CONVERTED/DISQUALIFIED terminal states are only reachable via
        // POST /leads/:id/convert and /disqualify.
        'status'      => 'permit_empty|in_list[NEW,CONTACTED,QUALIFIED]',
    ];

    public array $leadConvert = [
        'dealName'          => 'required|max_length[180]',
        'dealValue'         => 'permit_empty|decimal|greater_than_equal_to[0]',
        'existingCompanyId' => 'permit_empty|integer',
    ];

    public array $leadDisqualify = [
        'reason' => 'required|max_length[255]',
    ];

    public array $dealCreate = [
        'companyId'         => 'required|integer',
        'contactId'         => 'permit_empty|integer',
        'name'              => 'required|max_length[180]',
        'valueAmount'       => 'permit_empty|decimal|greater_than_equal_to[0]',
        'expectedCloseDate' => 'permit_empty|valid_date',
    ];

    public array $dealUpdate = [
        'name'              => 'permit_empty|max_length[180]',
        'contactId'         => 'permit_empty|integer',
        'valueAmount'       => 'permit_empty|decimal|greater_than_equal_to[0]',
        'expectedCloseDate' => 'permit_empty|valid_date',
    ];

    public array $dealChangeStage = [
        'stage'      => 'required|in_list[PROSPECTING,PROPOSAL,NEGOTIATION,WON,LOST]',
        'lostReason' => 'permit_empty|max_length[255]',
    ];

    public array $taskCreate = [
        'subject'       => 'required|max_length[200]',
        'dueDate'       => 'permit_empty|valid_date',
        'priority'      => 'required|in_list[LOW,MEDIUM,HIGH]',
        'relatedToType' => 'permit_empty|in_list[COMPANY,CONTACT,LEAD,DEAL]',
        'relatedToId'   => 'permit_empty|integer',
        'assignedTo'    => 'permit_empty|integer',
    ];

    public array $taskUpdate = [
        'subject'    => 'permit_empty|max_length[200]',
        'dueDate'    => 'permit_empty|valid_date',
        'priority'   => 'permit_empty|in_list[LOW,MEDIUM,HIGH]',
        'assignedTo' => 'permit_empty|integer',
    ];

    public array $activityCreate = [
        'type'          => 'required|in_list[NOTE,CALL,EMAIL,MEETING]',
        'subject'       => 'permit_empty|max_length[200]',
        'body'          => 'required|max_length[2000]',
        'occurredAt'    => 'permit_empty|valid_date',
        'relatedToType' => 'required|in_list[COMPANY,CONTACT,LEAD,DEAL]',
        'relatedToId'   => 'required|integer',
    ];
}
