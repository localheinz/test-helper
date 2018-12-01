<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/test-util
 */

namespace Localheinz\Test\Util\Test\Unit;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider;
use Localheinz\Test\Util\Exception;
use Localheinz\Test\Util\Helper;
use Localheinz\Test\Util\Test\Fixture;
use PHPUnit\Framework;

/**
 * @internal
 */
final class HelperTest extends Framework\TestCase
{
    use Helper;

    public function testHelperMethodsAreFinalAndProtected(): void
    {
        $className = Helper::class;

        $reflection = new \ReflectionClass($className);

        $methods = $reflection->getMethods();

        $methodsNeitherFinalNorProtected = \array_filter($methods, static function (\ReflectionMethod $method): bool {
            return !$method->isFinal() || !$method->isProtected();
        });

        self::assertEmpty($methodsNeitherFinalNorProtected, \sprintf(
            "Failed asserting that the methods \n\n%s\n\nare final and protected.",
            \implode("\n", \array_map(static function (\ReflectionMethod $method) use ($className): string {
                return \sprintf(
                    ' - %s::%s()',
                    $className,
                    $method->getName()
                );
            }, $methodsNeitherFinalNorProtected))
        ));
    }

    public function testFakerWithoutLocaleReturnsFakerWithDefaultLocale(): void
    {
        $faker = $this->faker();

        $this->assertHasOnlyProvidersWithLocale(Factory::DEFAULT_LOCALE, $faker);
    }

    /**
     * @dataProvider providerLocale
     *
     * @param string $locale
     */
    public function testFakerWithLocaleReturnsFakerWithSpecifiedLocale(string $locale): void
    {
        $faker = $this->faker($locale);

        $this->assertHasOnlyProvidersWithLocale($locale, $faker);
    }

    /**
     * @dataProvider providerLocale
     *
     * @param string $locale
     */
    public function testFakerReturnsSameFaker(string $locale): void
    {
        $faker = $this->faker($locale);

        self::assertSame($faker, $this->faker($locale));
    }

    public function providerLocale(): \Generator
    {
        /**
         * Note that \Faker\Factory::getProviderClassname() will fall back to using the default locale if it cannot find
         * a localized provider class name for one of the default providers - that's why the selection of locales here
         * is a bit limited.
         *
         * @see \Faker\Factory::$defaultProviders
         * @see \Faker\Factory::getProviderClassname()
         */
        $locales = [
            'de_DE',
            'en_US',
            'fr_FR',
        ];

        foreach ($locales as $locale) {
            yield $locale => [
                $locale,
            ];
        }
    }

    public function testAssertClassesAreAbstractOrFinalRejectsNonExistentDirectory(): void
    {
        $directory = __DIR__ . '/../Fixture/NonExistentDirectory';

        $this->expectException(Exception\NonExistentDirectory::class);

        $this->assertClassesAreAbstractOrFinal($directory);
    }

    public function testAssertClassesAreAbstractOrFinalFailsWhenFoundClassesAreNeitherAbstractNorFinal(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesAreAbstractOrFinal/NotAllAbstractOrFinal';
        $classesNeitherAbstractNorFinal = [
            Fixture\ClassesAreAbstractOrFinal\NotAllAbstractOrFinal\AlsoNeitherAbstractNorFinal::class,
            Fixture\ClassesAreAbstractOrFinal\NotAllAbstractOrFinal\NeitherAbstractNorFinal::class,
        ];

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            "Failed asserting that the classes\n\n%s\n\nare abstract or final.",
            ' - ' . \implode("\n - ", $classesNeitherAbstractNorFinal)
        ));

        $this->assertClassesAreAbstractOrFinal($directory);
    }

    public function testAssertClassesAreAbstractOrFinalSucceedsWhenNoClassesHaveBeenFound(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesAreAbstractOrFinal/EmptyDirectory';

        $this->assertClassesAreAbstractOrFinal($directory);
    }

    public function testAssertClassesAreAbstractOrFinalSucceedsWhenFoundClassesAreAbstractOrFinal(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesAreAbstractOrFinal/AbstractOrFinal';

        $this->assertClassesAreAbstractOrFinal($directory);
    }

    /**
     * @dataProvider providerInvalidExcludeClassyName
     *
     * @param mixed $excludeClassName
     */
    public function testAssertClassesAreAbstractOrFinalWithExcludeClassNamesRejectsInvalidExcludeClassNames($excludeClassName): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesAreAbstractOrFinal';
        $excludeClassNames = [
            $excludeClassName,
        ];

        $this->expectException(Exception\InvalidExcludeClassName::class);

        $this->assertClassesAreAbstractOrFinal(
            $directory,
            $excludeClassNames
        );
    }

    public function providerInvalidExcludeClassyName(): \Generator
    {
        $classyNames = [
            'array' => [
                'foo',
                'bar',
                'baz',
            ],
            'boolean-false' => false,
            'boolean-true' => true,
            'float' => 3.14,
            'integer' => 9000,
            'null' => null,
            'object' => new \stdClass(),
            'resource' => \fopen(__FILE__, 'rb'),
        ];

        foreach ($classyNames as $key => $classyName) {
            yield $key => [
                $classyName,
            ];
        }
    }

    public function testAssertClassesAreAbstractOrFinalWithExcludeClassNamesRejectsNonExistentExcludeClassNames(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesAreAbstractOrFinal';
        $nonExistentClassName = __NAMESPACE__ . '\\NonExistentClass';
        $excludeClassyNames = [
            Fixture\ClassesAreAbstractOrFinal\NotAllAbstractOrFinal\AlsoNeitherAbstractNorFinal::class,
            Fixture\ClassesAreAbstractOrFinal\NotAllAbstractOrFinal\NeitherAbstractNorFinal::class,
            $nonExistentClassName,
        ];

        $this->expectException(Exception\NonExistentExcludeClass::class);

        $this->assertClassesAreAbstractOrFinal(
            $directory,
            $excludeClassyNames
        );
    }

    public function testAssertClassesAreAbstractOrFinalWithExcludeClassNamesFailsWhenFoundClassesAreNeitherAbstractNorFinal(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesAreAbstractOrFinal/NotAllAbstractOrFinal';
        $excludeClassyNames = [
            Fixture\ClassesAreAbstractOrFinal\NotAllAbstractOrFinal\AlsoNeitherAbstractNorFinal::class,
        ];

        $classesNeitherAbstractNorFinal = [
            Fixture\ClassesAreAbstractOrFinal\NotAllAbstractOrFinal\NeitherAbstractNorFinal::class,
        ];

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            "Failed asserting that the classes\n\n%s\n\nare abstract or final.",
            ' - ' . \implode("\n - ", $classesNeitherAbstractNorFinal)
        ));

        $this->assertClassesAreAbstractOrFinal(
            $directory,
            $excludeClassyNames
        );
    }

    public function testAssertClassesAreAbstractOrFinalWithExcludeClassNamesSucceedsWhenFoundClassesAreAbstractOrFinal(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesAreAbstractOrFinal';
        $excludeClassyNames = [
            Fixture\ClassesAreAbstractOrFinal\NotAllAbstractOrFinal\AlsoNeitherAbstractNorFinal::class,
            Fixture\ClassesAreAbstractOrFinal\NotAllAbstractOrFinal\NeitherAbstractNorFinal::class,
        ];

        $this->assertClassesAreAbstractOrFinal(
            $directory,
            $excludeClassyNames
        );
    }

    public function testAssertClassesHaveTestsRejectsNonExistentDirectory(): void
    {
        $directory = __DIR__ . '/../Fixture/NonExistentDirectory';
        $namespace = '';
        $testNamespace = '';

        $this->expectException(Exception\NonExistentDirectory::class);

        $this->assertClassesHaveTests(
            $directory,
            $namespace,
            $testNamespace
        );
    }

    public function testAssertClassesHaveTestsFailsWhenFoundClassesDoNotHaveTests(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesHaveTests/WithoutTests/Src';
        $namespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\WithoutTests\\Src\\';
        $testNamespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\WithoutTests\\Test\\';

        $classesWithoutTests = [
            Fixture\ClassesHaveTests\WithoutTests\Src\AnotherExampleClass::class,
            Fixture\ClassesHaveTests\WithoutTests\Src\ExampleClass::class,
            Fixture\ClassesHaveTests\WithoutTests\Src\OneMoreExampleClass::class,
            Fixture\ClassesHaveTests\WithoutTests\Src\YetAnotherExampleClass::class,
        ];

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            "Failed asserting that the classes\n\n%s\n\nhave tests. Expected corresponding test classes\n\n%s\n\nextending from \"%s\" but could not find them.",
            \implode("\n", \array_map(static function (string $className): string {
                return \sprintf(
                    ' - %s',
                    $className
                );
            }, $classesWithoutTests)),
            \implode("\n", \array_map(static function (string $className) use ($namespace, $testNamespace): string {
                $testClassName = \str_replace(
                        $namespace,
                        $testNamespace,
                        $className
                    ) . 'Test';

                return \sprintf(
                    ' - %s',
                    $testClassName
                );
            }, $classesWithoutTests)),
            Framework\TestCase::class
        ));

        $this->assertClassesHaveTests(
            $directory,
            $namespace,
            $testNamespace
        );
    }

    public function testAssertClassesHaveTestsSucceedsWhenNoClassesHaveBeenFound(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesHaveTests/EmptyDirectory/Src';
        $namespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\EmptyDirectory\\Src\\';
        $testNamespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\EmptyDirectory\\Test\\';

        $this->assertClassesHaveTests(
            $directory,
            $namespace,
            $testNamespace
        );
    }

    public function testAssertClassesHaveTestsSucceedsWhenFoundClassesHaveTests(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesHaveTests/WithTests/Src';
        $namespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\WithTests\\Src\\';
        $testNamespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\WithTests\\Test\\';

        $this->assertClassesHaveTests(
            $directory,
            $namespace,
            $testNamespace
        );
    }

    /**
     * @dataProvider providerNamespaceAndTestNamespace
     *
     * @param string $namespace
     * @param string $testNamespace
     */
    public function testAssertClassesHaveTestsWorksWithAndWithoutTrailingSlash(string $namespace, string $testNamespace): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesHaveTests/WithTests/Src';

        $this->assertClassesHaveTests(
            $directory,
            $namespace,
            $testNamespace
        );
    }

    public function providerNamespaceAndTestNamespace(): \Generator
    {
        $namespaces = [
            'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\WithTests\\Src',
            'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\WithTests\\Src\\',
        ];

        $testNamespaces = [
            'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\WithTests\\Test',
            'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\WithTests\\Test\\',
        ];

        foreach ($namespaces as $namespace) {
            foreach ($testNamespaces as $testNamespace) {
                yield [
                    $namespace,
                    $testNamespace,
                ];
            }
        }
    }

    /**
     * @dataProvider providerInvalidExcludeClassyName
     *
     * @param mixed $excludeClassyName
     */
    public function testAssertClassesHaveTestsWithExcludeClassNamesRejectsInvalidExcludeClassNames($excludeClassyName): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesHaveTests/NotAllClassesHaveTests/Src';
        $namespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\NotAllClassesHaveTests\\Src\\';
        $testNamespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\NotAllClassesHaveTests\\Test\\';
        $excludeClassyNames = [
            $excludeClassyName,
        ];

        $this->expectException(Exception\InvalidExcludeClassName::class);

        $this->assertClassesHaveTests(
            $directory,
            $namespace,
            $testNamespace,
            $excludeClassyNames
        );
    }

    public function testAssertClassesHaveTestsWithExcludeClassNamesRejectsNonExistentExcludeClassNames(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesHaveTests/NotAllClassesHaveTests/Src';
        $namespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\NotAllClassesHaveTests\\Src\\';
        $testNamespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\NotAllClassesHaveTests\\Test\\';
        $nonExistentClassName = __NAMESPACE__ . '\\NonExistentClass';
        $excludeClassyNames = [
            Fixture\ClassesHaveTests\NotAllClassesHaveTests\Src\AnotherExampleClass::class,
            Fixture\ClassesHaveTests\NotAllClassesHaveTests\Src\OneMoreExampleClass::class,
            $nonExistentClassName,
        ];

        $this->expectException(Exception\NonExistentExcludeClass::class);

        $this->assertClassesHaveTests(
            $directory,
            $namespace,
            $testNamespace,
            $excludeClassyNames
        );
    }

    public function testAssertClassesHaveTestsWithExcludeClassNamesFailsWhenFoundClassesDoNotHaveTests(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesHaveTests/NotAllClassesHaveTests/Src';
        $namespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\NotAllClassesHaveTests\\Src\\';
        $testNamespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\NotAllClassesHaveTests\\Test\\';
        $excludeClassyNames = [
            Fixture\ClassesHaveTests\NotAllClassesHaveTests\Src\AnotherExampleClass::class,
        ];

        $classesWithoutTests = [
            Fixture\ClassesHaveTests\NotAllClassesHaveTests\Src\OneMoreExampleClass::class,
        ];

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            "Failed asserting that the classes\n\n%s\n\nhave tests.",
            ' - ' . \implode("\n - ", $classesWithoutTests)
        ));

        $this->assertClassesHaveTests(
            $directory,
            $namespace,
            $testNamespace,
            $excludeClassyNames
        );
    }

    public function testAssertClassesHaveTestsWithExcludeClassNamesSucceedsWhenFoundClassesHaveTests(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassesHaveTests/NotAllClassesHaveTests/Src';
        $namespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\NotAllClassesHaveTests\\Src\\';
        $testNamespace = 'Localheinz\\Test\\Util\\Test\\Fixture\\ClassesHaveTests\\NotAllClassesHaveTests\\Test\\';
        $excludeClassyNames = [
            Fixture\ClassesHaveTests\NotAllClassesHaveTests\Src\AnotherExampleClass::class,
            Fixture\ClassesHaveTests\NotAllClassesHaveTests\Src\OneMoreExampleClass::class,
        ];

        $this->assertClassesHaveTests(
            $directory,
            $namespace,
            $testNamespace,
            $excludeClassyNames
        );
    }

    public function testAssertClassyConstructsSatisfySpecificationRejectsNonExistentDirectory(): void
    {
        $directory = __DIR__ . '/../Fixture/NonExistentDirectory';

        $this->expectException(Exception\NonExistentDirectory::class);

        $this->assertClassyConstructsSatisfySpecification(
            static function (string $classyName): bool {
                return false;
            },
            $directory
        );
    }

    public function testAssertClassyConstructsSatisfySpecificationFailsWhenFoundClassesDoNotSatisfySpecification(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassyConstructsSatisfySpecification';
        $classesNotSatisfyingSpecification = [
            Fixture\ClassyConstructsSatisfySpecification\AnotherExampleClass::class,
            Fixture\ClassyConstructsSatisfySpecification\ExampleClass::class,
        ];

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            "Failed asserting that the classy constructs\n\n%s\n\nsatisfy a specification.",
            ' - ' . \implode("\n - ", $classesNotSatisfyingSpecification)
        ));

        $this->assertClassyConstructsSatisfySpecification(
            static function (string $className): bool {
                return false;
            },
            $directory
        );
    }

    public function testAssertClassyConstructsSatisfySpecificationSucceedsWhenNoClassesHaveBeenFound(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassyConstructsSatisfySpecification/EmptyDirectory';

        $this->assertClassyConstructsSatisfySpecification(
            static function (string $className): bool {
                return false;
            },
            $directory
        );
    }

    public function testAssertClassyConstructsSatisfySpecificationSucceedsWhenFoundClassesSatisfySpecification(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassyConstructsSatisfySpecification';

        $this->assertClassyConstructsSatisfySpecification(
            static function (string $className): bool {
                return true;
            },
            $directory
        );
    }

    /**
     * @dataProvider providerInvalidExcludeClassyName
     *
     * @param mixed $excludeClassyName
     */
    public function testAssertClassyConstructsSatisfySpecificationWithExcludeClassNamesRejectsInvalidExcludeClassNames($excludeClassyName): void
    {
        $directory = __DIR__ . '/../Fixture/ClassyConstructsSatisfySpecification';
        $excludeClassyNames = [
            $excludeClassyName,
        ];

        $this->expectException(Exception\InvalidExcludeClassName::class);

        $this->assertClassyConstructsSatisfySpecification(
            static function (string $classyName): bool {
                return true;
            },
            $directory,
            $excludeClassyNames
        );
    }

    public function testAssertClassyConstructsSpecificationWithExcludeClassNamesRejectsNonExistentExcludeClassNames(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassyConstructsSatisfySpecification';
        $nonExistentClassName = __NAMESPACE__ . '\\NonExistentClass';
        $excludeClassyNames = [
            Fixture\ClassyConstructsSatisfySpecification\AnotherExampleClass::class,
            $nonExistentClassName,
        ];

        $this->expectException(Exception\NonExistentExcludeClass::class);

        $this->assertClassyConstructsSatisfySpecification(
            static function (string $classyName): bool {
                return Fixture\ClassyConstructsSatisfySpecification\ExampleClass::class === $classyName;
            },
            $directory,
            $excludeClassyNames
        );
    }

    public function testAssertClassyConstructsSatisfySpecificationWithExcludeClassyNamesFailsWhenFoundClassyConstructsDoNotSatisfySpecification(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassyConstructsSatisfySpecification';
        $excludeClassyNames = [
            Fixture\ClassyConstructsSatisfySpecification\AnotherExampleClass::class,
        ];

        $classesNotSatisfyingSpecification = [
            Fixture\ClassyConstructsSatisfySpecification\ExampleClass::class,
        ];

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            "Failed asserting that the classy constructs\n\n%s\n\nsatisfy a specification.",
            ' - ' . \implode("\n - ", $classesNotSatisfyingSpecification)
        ));

        $this->assertClassyConstructsSatisfySpecification(
            static function (string $classyName): bool {
                return false;
            },
            $directory,
            $excludeClassyNames
        );
    }

    public function testAssertClassyConstructsSpecificationWithExcludeClassNamesSucceedsWhenFoundClassesSatisfySpecification(): void
    {
        $directory = __DIR__ . '/../Fixture/ClassyConstructsSatisfySpecification';
        $excludeClassyNames = [
            Fixture\ClassyConstructsSatisfySpecification\AnotherExampleClass::class,
        ];

        $this->assertClassyConstructsSatisfySpecification(
            static function (string $classyName): bool {
                return Fixture\ClassyConstructsSatisfySpecification\ExampleClass::class === $classyName;
            },
            $directory,
            $excludeClassyNames
        );
    }

    /**
     * @dataProvider providerNotClass
     *
     * @param string $className
     */
    public function testAssertClassExistsFailsWhenClassIsNotClass(string $className): void
    {
        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a class "%s" exists.',
            $className
        ));

        $this->assertClassExists($className);
    }

    public function providerNotClass(): \Generator
    {
        $classyNames = [
            'class-non-existent' => __NAMESPACE__ . '\NonExistentClass',
            'interface' => Fixture\NotClass\ExampleInterface::class,
            'trait' => Fixture\NotClass\ExampleTrait::class,
        ];

        foreach ($classyNames as $key => $classyName) {
            yield $key => [
                $classyName,
            ];
        }
    }

    public function testAssertClassExistsSucceedsWhenClassExists(): void
    {
        $className = Fixture\ClassExists\ExampleClass::class;

        $this->assertClassExists($className);
    }

    /**
     * @dataProvider providerNotClass
     *
     * @param string $parentClassName
     */
    public function testAssertClassExtendsFailsWhenParentClassIsNotClass(string $parentClassName): void
    {
        $className = Fixture\ClassExtends\ChildClass::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a class "%s" exists.',
            $parentClassName
        ));

        $this->assertClassExtends(
            $parentClassName,
            $className
        );
    }

    /**
     * @dataProvider providerNotClass
     *
     * @param string $className
     */
    public function testAssertClassExtendsFailsWhenClassIsNotClass(string $className): void
    {
        $parentClassName = Fixture\ClassExtends\ParentClass::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a class "%s" exists.',
            $className
        ));

        $this->assertClassExtends(
            $parentClassName,
            $className
        );
    }

    public function testClassExtendsFailsWhenClassDoesNotExtendParentClass(): void
    {
        $parentClassName = Fixture\ClassExtends\ParentClass::class;
        $className = Fixture\ClassExtends\UnrelatedClass::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that class "%s" extends "%s".',
            $className,
            $parentClassName
        ));

        $this->assertClassExtends(
            $parentClassName,
            $className
        );
    }

    public function testAssertClassExtendsSucceedsWhenClassExtendsParentClass(): void
    {
        $parentClassName = Fixture\ClassExtends\ParentClass::class;
        $className = Fixture\ClassExtends\ChildClass::class;

        $this->assertClassExtends(
            $parentClassName,
            $className
        );
    }

    /**
     * @dataProvider providerNotInterface
     *
     * @param string $interfaceName
     */
    public function testAssertClassImplementsInterfaceFailsWhenInterfaceIsNotInterface(string $interfaceName): void
    {
        $className = Fixture\ImplementsInterface\ClassImplementingInterface::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that an interface "%s" exists.',
            $interfaceName
        ));

        $this->assertClassImplementsInterface(
            $interfaceName,
            $className
        );
    }

    /**
     * @dataProvider providerNotClass
     *
     * @param string $className
     */
    public function testAssertClassImplementsInterfaceFailsWhenClassIsNotClass(string $className): void
    {
        $interfaceName = Fixture\ImplementsInterface\ExampleInterface::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a class "%s" exists.',
            $className
        ));

        $this->assertClassImplementsInterface(
            $interfaceName,
            $className
        );
    }

    public function testAssertClassImplementsInterfaceFailsWhenClassDoesNotImplementInterface(): void
    {
        $interfaceName = Fixture\ImplementsInterface\ExampleInterface::class;
        $className = Fixture\ImplementsInterface\ClassNotImplementingInterface::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that class "%s" implements interface "%s".',
            $className,
            $interfaceName
        ));

        $this->assertClassImplementsInterface(
            $interfaceName,
            $className
        );
    }

    public function testAssertClassImplementsInterfaceSucceedsWhenClassImplementsInterface(): void
    {
        $interfaceName = Fixture\ImplementsInterface\ExampleInterface::class;
        $className = Fixture\ImplementsInterface\ClassImplementingInterface::class;

        $this->assertClassImplementsInterface(
            $interfaceName,
            $className
        );
    }

    /**
     * @dataProvider providerNotClass
     *
     * @param string $className
     */
    public function testAssertClassIsAbstractFailsWhenClassIsNotClass(string $className): void
    {
        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a class "%s" exists.',
            $className
        ));

        $this->assertClassIsAbstract($className);
    }

    public function testAssertClassIsAbstractFailsWhenClassIsNotAbstract(): void
    {
        $className = Fixture\ClassIsAbstract\ConcreteClass::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that class "%s" is abstract.',
            $className
        ));

        $this->assertClassIsAbstract($className);
    }

    public function testAssertClassIsAbstractSucceedsWhenClassIsAbstract(): void
    {
        $className = Fixture\ClassIsAbstract\AbstractClass::class;

        $this->assertClassIsAbstract($className);
    }

    /**
     * @dataProvider providerNotClass
     *
     * @param string $className
     */
    public function testAssertClassIsFinalFailsWhenClassIsNotClass(string $className): void
    {
        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a class "%s" exists.',
            $className
        ));

        $this->assertClassIsFinal($className);
    }

    public function testAssertClassIsFinalFailsWhenClassIsNotFinal(): void
    {
        $className = Fixture\ClassIsFinal\NeitherAbstractNorFinalClass::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that class "%s" is final.',
            $className
        ));

        $this->assertClassIsFinal($className);
    }

    public function testAssertClassIsFinalSucceedsWhenClassIsFinal(): void
    {
        $className = Fixture\ClassIsFinal\FinalClass::class;

        $this->assertClassIsFinal($className);
    }

    /**
     * @dataProvider providerNotClass
     *
     * @param string $className
     */
    public function testAssertClassSatisfiesSpecificationFailsWhenClassIsNotAClass(string $className): void
    {
        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a class "%s" exists.',
            $className
        ));

        $this->assertClassSatisfiesSpecification(
            static function (): bool {
                return true;
            },
            $className
        );
    }

    public function testAssertClassSatisfiesSpecificationFailsWhenSpecificationReturnsFalse(): void
    {
        $className = Fixture\ClassSatisfiesSpecification\ExampleClass::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that class "%s" satisfies a specification.',
            $className
        ));

        $this->assertClassSatisfiesSpecification(
            static function (): bool {
                return false;
            },
            $className
        );
    }

    public function testAssertClassSatisfiesSpecificationFailsWhenSpecificationReturnsFalseAndUsesMessage(): void
    {
        $className = Fixture\ClassSatisfiesSpecification\ExampleClass::class;
        $message = 'Looks like "%s" does not satisfy our requirements right now';

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            $message,
            $className
        ));

        $this->assertClassSatisfiesSpecification(
            static function (): bool {
                return false;
            },
            $className,
            $message
        );
    }

    public function testAssertClassSatisfiesSpecificationSucceedsWhenSpecificationReturnsTrue(): void
    {
        $className = Fixture\ClassSatisfiesSpecification\ExampleClass::class;

        $this->assertClassSatisfiesSpecification(
            static function (): bool {
                return true;
            },
            $className
        );
    }

    /**
     * @dataProvider providerNotTrait
     *
     * @param string $traitName
     */
    public function testAssertClassUsesTraitFailsWhenTraitIsNotTrait(string $traitName): void
    {
        $className = Fixture\ClassUsesTrait\ClassUsingTrait::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a trait "%s" exists.',
            $traitName
        ));

        $this->assertClassUsesTrait(
            $traitName,
            $className
        );
    }

    /**
     * @dataProvider providerNotClass
     *
     * @param string $className
     */
    public function testAssertClassUsesTraitFailsWhenClassIsNotClass(string $className): void
    {
        $traitName = Fixture\ClassUsesTrait\ExampleTrait::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a class "%s" exists.',
            $className
        ));

        $this->assertClassUsesTrait(
            $traitName,
            $className
        );
    }

    public function testAssertClassUsesTraitFailsWhenClassDoesNotUseTrait(): void
    {
        $traitName = Fixture\ClassUsesTrait\ExampleTrait::class;
        $className = Fixture\ClassUsesTrait\ClassNotUsingTrait::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that class "%s" uses trait "%s".',
            $className,
            $traitName
        ));

        $this->assertClassUsesTrait(
            $traitName,
            $className
        );
    }

    public function testAssertClassUsesTraitSucceedsWhenClassUsesTrait(): void
    {
        $traitName = Fixture\ClassUsesTrait\ExampleTrait::class;
        $className = Fixture\ClassUsesTrait\ClassUsingTrait::class;

        $this->assertClassUsesTrait(
            $traitName,
            $className
        );
    }

    /**
     * @dataProvider providerNotInterface
     *
     * @param string $interfaceName
     */
    public function testAssertInterfaceExistsFailsWhenInterfaceIsNotInterface(string $interfaceName): void
    {
        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that an interface "%s" exists.',
            $interfaceName
        ));

        $this->assertInterfaceExists($interfaceName);
    }

    public function providerNotInterface(): \Generator
    {
        $interfaceNames = [
            'class' => Fixture\NotInterface\ExampleClass::class,
            'interface-non-existent' => __NAMESPACE__ . '\NonExistentInterface',
            'trait' => Fixture\NotInterface\ExampleTrait::class,
        ];

        foreach ($interfaceNames as $key => $interfaceName) {
            yield $key => [
                $interfaceName,
            ];
        }
    }

    public function testAssertInterfaceExistsSucceedsWhenInterfaceExists(): void
    {
        $interfaceName = Fixture\InterfaceExists\ExampleInterface::class;

        $this->assertInterfaceExists($interfaceName);
    }

    /**
     * @dataProvider providerNotInterface
     *
     * @param string $parentInterfaceName
     */
    public function testInterfaceExtendsFailsWhenParentInterfaceIsNotInterface(string $parentInterfaceName): void
    {
        $interfaceName = Fixture\InterfaceExtends\ChildInterface::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that an interface "%s" exists.',
            $parentInterfaceName
        ));

        $this->assertInterfaceExtends(
            $parentInterfaceName,
            $interfaceName
        );
    }

    /**
     * @dataProvider providerNotInterface
     *
     * @param string $interfaceName
     */
    public function testAssertInterfaceExtendsFailsWhenInterfaceIsNotInterface(string $interfaceName): void
    {
        $parentInterfaceName = Fixture\InterfaceExtends\ParentInterface::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that an interface "%s" exists.',
            $interfaceName
        ));

        $this->assertInterfaceExtends(
            $parentInterfaceName,
            $interfaceName
        );
    }

    public function testAssertInterfaceExtendsFailsWhenInterfaceDoesNotExtendParentInterface(): void
    {
        $parentInterfaceName = Fixture\InterfaceExtends\ParentInterface::class;
        $interfaceName = Fixture\InterfaceExtends\UnrelatedInterface::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that interface "%s" extends "%s".',
            $interfaceName,
            $parentInterfaceName
        ));

        $this->assertInterfaceExtends(
            $parentInterfaceName,
            $interfaceName
        );
    }

    public function testAssertInterfaceExtendsSucceedsWhenInterfaceExtendsParentInterface(): void
    {
        $parentInterfaceName = Fixture\InterfaceExtends\ParentInterface::class;
        $interfaceName = Fixture\InterfaceExtends\ChildInterface::class;

        $this->assertInterfaceExtends(
            $parentInterfaceName,
            $interfaceName
        );
    }

    /**
     * @dataProvider providerNotInterface
     *
     * @param string $interfaceName
     */
    public function testAssertInterfaceSatisfiesSpecificationFailsWhenInterfaceIsNotAInterface(string $interfaceName): void
    {
        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that an interface "%s" exists.',
            $interfaceName
        ));

        $this->assertInterfaceSatisfiesSpecification(
            static function (): bool {
                return true;
            },
            $interfaceName
        );
    }

    public function testAssertInterfaceSatisfiesSpecificationFailsWhenSpecificationReturnsFalse(): void
    {
        $interfaceName = Fixture\InterfaceSatisfiesSpecification\ExampleInterface::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that interface "%s" satisfies a specification.',
            $interfaceName
        ));

        $this->assertInterfaceSatisfiesSpecification(
            static function (): bool {
                return false;
            },
            $interfaceName
        );
    }

    public function testAssertInterfaceSatisfiesSpecificationFailsWhenSpecificationReturnsFalseAndUsesMessage(): void
    {
        $interfaceName = Fixture\InterfaceSatisfiesSpecification\ExampleInterface::class;
        $message = 'Looks like "%s" does not satisfy our requirements right now';

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            $message,
            $interfaceName
        ));

        $this->assertInterfaceSatisfiesSpecification(
            static function (): bool {
                return false;
            },
            $interfaceName,
            $message
        );
    }

    public function testAssertInterfaceSatisfiesSpecificationSucceedsWhenSpecificationReturnsTrue(): void
    {
        $interfaceName = Fixture\InterfaceSatisfiesSpecification\ExampleInterface::class;

        $this->assertInterfaceSatisfiesSpecification(
            static function (): bool {
                return true;
            },
            $interfaceName
        );
    }

    /**
     * @dataProvider providerNotTrait
     *
     * @param string $traitName
     */
    public function testAssertTraitExistsFailsWhenTraitIsNotTrait(string $traitName): void
    {
        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a trait "%s" exists.',
            $traitName
        ));

        $this->assertTraitExists($traitName);
    }

    public function providerNotTrait(): \Generator
    {
        $traitNames = [
            'class' => Fixture\NotTrait\ExampleClass::class,
            'interface' => Fixture\NotTrait\ExampleInterface::class,
            'trait-non-existent' => __NAMESPACE__ . '\NonExistentTrait',
        ];

        foreach ($traitNames as $key => $traitName) {
            yield $key => [
                $traitName,
            ];
        }
    }

    public function testAssertTraitExistsSucceedsWhenTraitExists(): void
    {
        $traitName = Fixture\TraitExists\ExampleTrait::class;

        $this->assertTraitExists($traitName);
    }

    /**
     * @dataProvider providerNotTrait
     *
     * @param string $traitName
     */
    public function testAssertTraitSatisfiesSpecificationFailsWhenTraitIsNotATrait(string $traitName): void
    {
        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that a trait "%s" exists.',
            $traitName
        ));

        $this->assertTraitSatisfiesSpecification(
            static function (): bool {
                return true;
            },
            $traitName
        );
    }

    public function testAssertTraitSatisfiesSpecificationFailsWhenSpecificationReturnsFalse(): void
    {
        $traitName = Fixture\TraitSatisfiesSpecification\ExampleTrait::class;

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed asserting that trait "%s" satisfies a specification.',
            $traitName
        ));

        $this->assertTraitSatisfiesSpecification(
            static function (): bool {
                return false;
            },
            $traitName
        );
    }

    public function testAssertTraitSatisfiesSpecificationFailsWhenSpecificationReturnsFalseAndUsesMessage(): void
    {
        $traitName = Fixture\TraitSatisfiesSpecification\ExampleTrait::class;
        $message = 'Looks like "%s" does not satisfy our requirements right now';

        $this->expectException(Framework\AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf(
            $message,
            $traitName
        ));

        $this->assertTraitSatisfiesSpecification(
            static function (): bool {
                return false;
            },
            $traitName,
            $message
        );
    }

    public function testAssertTraitSatisfiesSpecificationSucceedsWhenSpecificationReturnsTrue(): void
    {
        $traitName = Fixture\TraitSatisfiesSpecification\ExampleTrait::class;

        $this->assertTraitSatisfiesSpecification(
            static function (): bool {
                return true;
            },
            $traitName
        );
    }

    private function assertHasOnlyProvidersWithLocale(string $locale, Generator $faker): void
    {
        $providerClasses = \array_map(static function (Provider\Base $provider): string {
            return \get_class($provider);
        }, $faker->getProviders());

        $providerLocales = \array_reduce(
            $providerClasses,
            static function (array $providerLocales, string $providerClass): array {
                if (0 === \preg_match('/^Faker\\\\Provider\\\\(?P<locale>[a-z]{2}_[A-Z]{2})\\\\/', $providerClass, $matches)) {
                    return $providerLocales;
                }

                $providerLocales[] = $matches['locale'];

                return $providerLocales;
            },
            []
        );

        $locales = \array_values(\array_unique($providerLocales));

        $expected = [
            $locale,
        ];

        self::assertEquals($expected, $locales);
    }
}
