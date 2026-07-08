@echo off

rem Iterate over each service directory and run Laravel tests
for /d %%D in (services\*) do (
    if exist "%%D\artisan" (
        echo =============================
        echo Running tests in %%D
        pushd %%D
        php artisan test
        popd
    )
)

echo All backend tests completed.
