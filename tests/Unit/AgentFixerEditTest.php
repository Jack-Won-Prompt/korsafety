<?php

namespace Tests\Unit;

use App\Services\AgentFixer;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 받은 수정 덩이를 파일에 끼워 넣는 부분을 본다.
 *
 * 여기가 어긋나면 엉뚱한 자리를 고치거나 파일이 반쪽이 된다.
 * 실제로 파일 전문을 받아 덮어쓰던 시절 shop.css 가 그렇게 반토막 났다.
 */
class AgentFixerEditTest extends TestCase
{
    private function applyEdits(string $before, string $text): ?string
    {
        $method = new ReflectionMethod(AgentFixer::class, 'applyEdits');
        $method->setAccessible(true);

        return $method->invoke(new AgentFixer(), $before, $text, 'test.php');
    }

    private function block(string $search, string $replace): string
    {
        return "<<<<<<< SEARCH\n{$search}\n=======\n{$replace}\n>>>>>>> REPLACE";
    }

    #[Test]
    public function 한_자리를_바꾼다(): void
    {
        $before = "첫 줄\n둘째 줄\n셋째 줄\n";

        $after = $this->applyEdits($before, $this->block('둘째 줄', '바뀐 줄'));

        $this->assertSame("첫 줄\n바뀐 줄\n셋째 줄\n", $after);
    }

    #[Test]
    public function 여러_자리를_차례로_바꾼다(): void
    {
        $before = "가\n나\n다\n";

        $after = $this->applyEdits(
            $before,
            $this->block('가', '하')."\n\n".$this->block('다', '카')
        );

        $this->assertSame("하\n나\n카\n", $after);
    }

    #[Test]
    public function 여러_줄짜리_덩이도_들어간다(): void
    {
        $before = "<?php\n\nfunction a() {\n    return 1;\n}\n";

        $after = $this->applyEdits(
            $before,
            $this->block("function a() {\n    return 1;\n}", "function a() {\n    // 둘을 돌려준다\n    return 2;\n}")
        );

        $this->assertStringContainsString('return 2;', $after);
        $this->assertStringContainsString('// 둘을 돌려준다', $after);
        $this->assertStringStartsWith("<?php\n", $after);
    }

    #[Test]
    public function 찾을_자리가_없으면_통째로_버린다(): void
    {
        $before = "첫 줄\n둘째 줄\n";

        $this->assertNull($this->applyEdits($before, $this->block('없는 줄', '새 줄')));
    }

    #[Test]
    public function 찾을_자리가_여러_군데면_통째로_버린다(): void
    {
        $before = "같은 줄\n다른 줄\n같은 줄\n";

        $this->assertNull($this->applyEdits($before, $this->block('같은 줄', '바뀐 줄')));
    }

    #[Test]
    public function 한_덩이라도_어긋나면_앞의_것까지_버린다(): void
    {
        $before = "가\n나\n다\n";

        // 첫 덩이는 들어가지만 둘째가 없는 자리를 가리킨다 — 반만 고친 파일을 내보내면 안 된다
        $after = $this->applyEdits(
            $before,
            $this->block('가', '하')."\n\n".$this->block('없는 줄', '새 줄')
        );

        $this->assertNull($after);
    }

    #[Test]
    public function 덩이가_하나도_없으면_버린다(): void
    {
        $this->assertNull($this->applyEdits("가\n나\n", '고칠 것이 없습니다.'));
    }

    #[Test]
    public function 잘린_덩이는_버린다(): void
    {
        // 답이 길이 한도에 걸려 REPLACE 표시를 못 찍고 끝난 모양
        $truncated = "<<<<<<< SEARCH\n나\n=======\n바뀐 줄";

        $this->assertNull($this->applyEdits("가\n나\n다\n", $truncated));
    }

    #[Test]
    public function 지우기만_하는_덩이도_받는다(): void
    {
        $before = "가\n버릴 줄\n다\n";

        $after = $this->applyEdits($before, "<<<<<<< SEARCH\n버릴 줄\n\n=======\n>>>>>>> REPLACE");

        $this->assertStringNotContainsString('버릴 줄', (string) $after);
        $this->assertStringContainsString('가', (string) $after);
        $this->assertStringContainsString('다', (string) $after);
    }

    #[Test]
    public function 찾을_자리가_비어_있으면_버린다(): void
    {
        $this->assertNull($this->applyEdits("가\n나\n", "<<<<<<< SEARCH\n\n=======\n새 줄\n>>>>>>> REPLACE"));
    }

    #[Test]
    public function 줄_가운데만_집어도_들여쓰기는_그대로_남는다(): void
    {
        $before = "function a() {\n    return 1;\n}\n";

        // 줄 전체가 아니라 가운데 조각만 집어도 된다. 앞의 공백은 건드리지 않으므로
        // 들여쓰기가 흐트러지지 않는다.
        $after = $this->applyEdits($before, $this->block('return 1;', 'return 2;'));

        $this->assertSame("function a() {\n    return 2;\n}\n", $after);
    }
}
