<?php

namespace Tests\Unit;

use App\Support\LineDiff;
use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function test_matches_rfc6238_reference_vector(): void
    {
        // RFC 6238 appendix B: ASCII "12345678901234567890", T=59 → 94287082 (last 6 digits).
        $this->assertSame('287082', Totp::code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 59));
        $this->assertSame('081804', Totp::code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 1111111109));
    }

    public function test_verify_accepts_current_code_and_rejects_others(): void
    {
        $secret = Totp::generateSecret();
        $this->assertTrue(Totp::verify($secret, Totp::code($secret)));
        $this->assertFalse(Totp::verify($secret, '000000') && Totp::code($secret) !== '000000');
        $this->assertFalse(Totp::verify($secret, 'abc'));
    }

    public function test_line_diff_counts(): void
    {
        $this->assertSame([2, 0], LineDiff::stats(null, "a\nb"));
        $this->assertSame([1, 1], LineDiff::stats("a\nb\nc", "a\nx\nc"));
        $this->assertSame([0, 3], LineDiff::stats("a\nb\nc", null));
    }
}
