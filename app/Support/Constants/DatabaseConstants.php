<?php

namespace App\Support\Constants;


final class DatabaseConstants
{

    /**
     * Connexion base centrale SaaS
     */
    public const LANDLORD_CONNECTION = 'landlord';


    /**
     * Connexion dynamique tenant
     */
    public const TENANT_CONNECTION = 'tenant';


    /**
     * Préfixe des bases tenant
     */
    public const TENANT_DATABASE_PREFIX = 'tenant_';


    /**
     * Charset MySQL
     */
    public const MYSQL_CHARSET = 'utf8mb4';


    /**
     * Collation MySQL
     */
    public const MYSQL_COLLATION = 'utf8mb4_unicode_ci';

}
