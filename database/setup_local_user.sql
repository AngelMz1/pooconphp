-- Create a user for the application
DO
$do$
BEGIN
   IF NOT EXISTS (
      SELECT FROM pg_catalog.pg_roles
      WHERE  rolname = 'local_user') THEN

      CREATE ROLE local_user LOGIN PASSWORD 'local_password';
   END IF;
END
$do$;

-- Grant permissions on database
GRANT ALL PRIVILEGES ON DATABASE pooconphp_local TO local_user;
ALTER DATABASE pooconphp_local OWNER TO local_user;

-- Grant permissions on schema public
\c pooconphp_local
GRANT ALL ON SCHEMA public TO local_user;
