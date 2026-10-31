<?php

namespace App\Providers;

use App\Models\AllowedStudent;
use App\Models\Answer;
use App\Models\Channel;
use App\Models\ChannelMute;
use App\Models\Club;
use App\Models\Event;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Message;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Report;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Short, stable morph aliases. "grade"/"class"/"profile" are also the audience scope types.
        Relation::enforceMorphMap([
            'user' => User::class,
            'grade' => Grade::class,
            'class' => SchoolClass::class,
            'profile' => Profile::class,
            'exam' => Exam::class,
            'material' => Material::class,
            'question' => Question::class,
            'answer' => Answer::class,
            'report' => Report::class,
            'club' => Club::class,
            'event' => Event::class,
            'channel' => Channel::class,
            'message' => Message::class,
            'channel_mute' => ChannelMute::class,
            'allowed_student' => AllowedStudent::class,
        ]);

        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        $perUser = fn (Request $request) => $request->user()?->id ?: $request->ip();
        RateLimiter::for('questions', fn (Request $request) => Limit::perMinute(5)->by($perUser($request)));
        RateLimiter::for('answers', fn (Request $request) => Limit::perMinute(10)->by($perUser($request)));
        RateLimiter::for('votes', fn (Request $request) => Limit::perMinute(60)->by($perUser($request)));
        RateLimiter::for('messages', fn (Request $request) => Limit::perMinute(20)->by($perUser($request)));
        RateLimiter::for('reports', fn (Request $request) => Limit::perMinute(10)->by($perUser($request)));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
    }
}
