<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AIModel;
use App\Models\ApiKey;
use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\ContactTag;
use App\Models\ContactRule;
use App\Models\ContactCustomField;
use App\Models\Conversation;
use App\Models\ConversationSession;
use App\Models\Memory;
use App\Models\Message;
use App\Models\Topic;
use App\Models\AgentTask;
use Illuminate\Database\Seeder;

class Phase02Seeder extends Seeder
{
    public function run(): void
    {
        $topics = Topic::factory()->count(5)->create();

        Contact::factory()
            ->count(8)
            ->create()
            ->each(function (Contact $contact) use ($topics) {
                $conversation = Conversation::factory()->for($contact)->for($topics->random())->create();
                ConversationSession::factory()->for($conversation)->create();
                Message::factory()->for($conversation)->count(4)->create();
                Memory::factory()->for($contact)->for($conversation)->count(2)->create();
                ContactNote::factory()->for($contact)->create();
                ContactTag::factory()->for($contact)->create();
                ContactRule::factory()->for($contact)->create();
                ContactCustomField::factory()->for($contact)->create();
            });

        Agent::factory()
            ->count(4)
            ->create()
            ->each(function (Agent $agent) {
                $agent->tasks()->saveMany(AgentTask::factory()->count(2)->make());
            });

        AIModel::factory()->count(3)->create();
        ApiKey::factory()->count(3)->create();
    }
}
