<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offence extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'source', //THE CYBER SECURITY AND CYBER CRIMES ACT, 2021, Penal code cap 87, The Anti-Terrorism and Non-Proliferation (Amendment) Act, 2024( Act No. 30 of 2024),The Appropriation Act, 2024( Act No. 29 of 2024), Insurance Premium Levy (Amendment) Act, 2024( Act No. 28 of 2024),Property Transfer Tax (Amendment) Act, 2024( Act No. 27 of 2024), Zambia Revenue Authority (Amendment) Act, 2024( Act No. 26 of 2024), The Moblie Money Transactions Levy Act, 2024( Act No. 25 of 2024), customs and Excise (Amendment) Act, 2024( Act No. 24 of 2024), Value Added Tax (Amendment) Act, 2024( Act No. 23 of 2024), Income Tax (Amendment) Act, 2024( Act No. 22 of 2024),Local Authorities Superannuation Fund (Amendment) Act, 2024( Act No. 21 of 2024), Supplementary Appropriation (2024) (No.2) Act 2024( Act No. 20 of 2024),
        'section',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

}
