<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Conversation;
use App\Models\Message;

class LiveChat extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Trực Chat (Live)';
    protected static ?string $title = 'Hỗ trợ khách hàng';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.live-chat';

    // CÁC BIẾN CHO GIAO DIỆN
    public $conversations;
    public $activeConversation = null;
    public $messages = [];
    public $newMessage = '';
    
    // BIẾN MỚI: Dành cho tính năng CHIA TAB
    public $currentTab = 'waiting'; 

    public function mount()
    {
        $this->loadConversations();
    }

    // Load danh sách khách (Lấy cả khách đang chờ và khách đang được xử lý)
    public function loadConversations()
    {
        $this->conversations = Conversation::whereIn('status', ['WAITING_FOR_AGENT', 'AGENT_HANDLING'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    // Bấm vào 1 khách để xem nội dung
    public function selectConversation($id)
    {
        $this->activeConversation = Conversation::find($id);
        $this->loadMessages();
    }

    // TÍNH NĂNG MỚI: Nhân viên bấm nút "Nhận ca này ngay"
    public function assignToMe()
    {
        if ($this->activeConversation && $this->activeConversation->status === 'WAITING_FOR_AGENT') {
            $this->activeConversation->update([
                'status' => 'AGENT_HANDLING',
                'assigned_to' => auth()->id() // Gán ID của nhân viên đang đăng nhập
            ]);
            $this->currentTab = 'mine'; // Chuyển luôn sang tab "Ca của tôi"
            $this->loadConversations();
        }
    }

    // TÍNH NĂNG MỚI: Bấm nút "✖ Kết thúc"
    public function closeConversation()
    {
        if ($this->activeConversation) {
            $this->activeConversation->update([
                'status' => 'RESOLVED' // Đóng ca chat
            ]);
            $this->activeConversation = null;
            $this->messages = [];
            $this->loadConversations();
        }
    }

    // Tải tin nhắn của ca đang chọn
    public function loadMessages()
    {
        if ($this->activeConversation) {
            $this->messages = Message::where('conversation_id', $this->activeConversation->id)->get();
        }
    }

    // Gửi tin nhắn
    public function sendMessage()
    {
        if (!$this->newMessage || !$this->activeConversation) return;

        Message::create([
            'conversation_id' => $this->activeConversation->id,
            'sender_type' => 'agent',
            'content' => $this->newMessage,
        ]);

        $this->newMessage = ''; 
        $this->loadMessages();  
    }
}