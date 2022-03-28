<?php

namespace Drupal\am_net_membership;

/**
 * Interface for store AMNet Member Status Codes.
 */
interface MemberStatusCodesInterface {

  /**
   * Membership status code: Applicant for Membership.
   */
  const APPLICANT_FOR_MEMBERSHIP = 'A';

  /**
   * Membership status code: Deceased.
   */
  const DECEASED = 'D';

  /**
   * Membership status code: Member in Good Standing.
   */
  const MEMBER_IN_GOOD_STANDING = 'M';

  /**
   * Membership status code: Member With a Dues Balance Or Prospective Member.
   */
  const MEMBER_WITH_A_DUES_BALANCE = 'L';

  /**
   * Membership status code: Terminated.
   */
  const TERMINATED = 'T';

  /**
   * Membership status code: Suspended Member.
   */
  const SUSPENDED_MEMBER = 'S';

  /**
   * Membership status code: Resigned.
   */
  const RESIGNED = 'R';

  /**
   * Membership status code: Nonmember.
   */
  const NON_MEMBER = 'N';

  /**
   * Role ID member.
   */
  const ROLE_ID_MEMBER = 'member';

  /**
   * Role ID firm_administrator.
   */
  const ROLE_ID_FIRM_ADMINISTRATOR = 'firm_administrator';

}
